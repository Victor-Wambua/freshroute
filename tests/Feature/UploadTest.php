<?php

use App\Enums\Role;
use App\Models\ListingPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function jpegBytes(int $w = 64, int $h = 48, bool $withExif = false): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, 30, 120, 60));
    ob_start();
    imagejpeg($im);
    $j = ob_get_clean();
    if ($withExif) {
        $exif = "Exif\0\0".'GPS-LAT-1.2921-LON-36.8219-SECRET-CAMERA-ID';
        $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
        $j = substr($j, 0, 2).$app1.substr($j, 2);
    }

    return $j;
}

function fakeFile(string $bytes, string $name = 'p.jpg', string $mime = 'image/jpeg'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'up');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, $mime, null, true);
}

beforeEach(function () {
    Storage::fake('local');
    $this->farmer = makeUser(Role::Farmer);
    $this->listing = makeListing($this->farmer);
});

it('accepts a valid JPEG, re-encodes it, and stores it privately', function () {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())])->assertRedirect();
    $p = ListingPhoto::first();
    expect($p->mime)->toBe('image/jpeg')->and($p->path)->toStartWith('listing-photos/')->and($p->sha256)->toHaveLength(64);
    Storage::disk('local')->assertExists($p->path);
    expect(file_exists(public_path($p->path)))->toBeFalse();
});

it('strips EXIF and GPS metadata by re-encoding', function () {
    $src = jpegBytes(64, 48, true);
    expect($src)->toContain('GPS-LAT');
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile($src)]);
    $stored = Storage::disk('local')->get(ListingPhoto::first()->path);
    expect($stored)->not->toContain('GPS-LAT')->not->toContain('SECRET-CAMERA-ID')->and(substr($stored, 0, 3))->toBe("\xFF\xD8\xFF");
});

it('accepts PNG and WebP and converts them to JPEG', function () {
    $im = imagecreatetruecolor(40, 40);
    ob_start();
    imagepng($im);
    $png = ob_get_clean();
    ob_start();
    imagewebp($im);
    $webp = ob_get_clean();
    foreach ([[$png, 'a.png', 'image/png'], [$webp, 'a.webp', 'image/webp']] as [$bytes, $n, $m]) {
        actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile($bytes, $n, $m)])->assertSessionDoesntHaveErrors();
    }
    expect(ListingPhoto::pluck('mime')->unique()->all())->toBe(['image/jpeg'])->and(ListingPhoto::count())->toBe(2);
});

it('rejects non-images regardless of the claimed name or MIME type (magic bytes)', function (string $bytes, string $name, string $mime) {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile($bytes, $name, $mime)])->assertSessionHasErrors('photo');
    expect(ListingPhoto::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'php script as jpg' => ['<?php system($_GET["c"]); ?>', 'shell.jpg', 'image/jpeg'],
    'html as png' => ['<html><script>alert(1)</script></html>', 'x.png', 'image/png'],
    'svg with script' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'x.svg', 'image/svg+xml'],
    'gif' => ["GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;", 'x.gif', 'image/gif'],
    'empty file' => ['', 'x.jpg', 'image/jpeg'],
    'truncated jpeg header only' => ["\xFF\xD8\xFF\xE0\x00\x10JFIF", 'x.jpg', 'image/jpeg'],
    'pdf' => ["%PDF-1.4\n1 0 obj<<>>endobj", 'x.pdf', 'application/pdf'],
]);

it('neutralises a polyglot: a JPEG with a PHP payload appended is re-encoded without the payload', function () {
    $polyglot = jpegBytes().'<?php echo "pwned"; ?>';
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile($polyglot, 'p.php.jpg')]);
    $stored = Storage::disk('local')->get(ListingPhoto::first()->path);
    expect($stored)->not->toContain('<?php')->not->toContain('pwned');
});

it('rejects oversized files and absurd dimensions (decompression bomb)', function () {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes().str_repeat('A', 5 * 1024 * 1024))])->assertSessionHasErrors('photo');
    // a PNG header that claims 30000 x 30000 pixels
    $ihdr = pack('N', 30000).pack('N', 30000)."\x08\x02\x00\x00\x00";
    $png = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr)).pack('N', 0).'IEND'.pack('N', crc32('IEND'));
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile($png, 'b.png', 'image/png')])->assertSessionHasErrors('photo');
    expect(ListingPhoto::count())->toBe(0);
});

it('downsizes large images', function () {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes(3000, 2000))]);
    [$w, $h] = getimagesizefromstring(Storage::disk('local')->get(ListingPhoto::first()->path));
    expect(max($w, $h))->toBeLessThanOrEqual(1600);
});

it('limits photos per listing and requires ownership to upload', function () {
    for ($i = 0; $i < 4; $i++) {
        actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())]);
    }
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())])->assertSessionHasErrors('photo');
    actingAsUser(makeUser(Role::Farmer))->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())])->assertForbidden();
    actingAsUser(makeUser(Role::Buyer))->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())])->assertForbidden();
    expect(ListingPhoto::count())->toBe(4);
});

it('serves photos only through an authorised route with safe headers', function () {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())]);
    $p = ListingPhoto::first();
    auth()->logout();
    $this->get("/photos/{$p->id}")->assertRedirect('/login');
    actingAsUser(makeUser(Role::Buyer))->get("/photos/{$p->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
    // a draft listing's photos are not visible to buyers or other farmers
    $this->listing->update(['status' => 'draft']);
    actingAsUser(makeUser(Role::Buyer))->get("/photos/{$p->id}")->assertForbidden();
    actingAsUser(makeUser(Role::Farmer))->get("/photos/{$p->id}")->assertForbidden();
    actingAsUser($this->farmer)->get("/photos/{$p->id}")->assertOk();
});

it('only lets the owner remove a photo and deletes the stored file', function () {
    actingAsUser($this->farmer)->post("/listings/{$this->listing->id}/photos", ['photo' => fakeFile(jpegBytes())]);
    $p = ListingPhoto::first();
    actingAsUser(makeUser(Role::Farmer))->delete("/photos/{$p->id}")->assertForbidden();
    actingAsUser($this->farmer)->delete("/photos/{$p->id}")->assertRedirect();
    Storage::disk('local')->assertMissing($p->path);
    expect(ListingPhoto::count())->toBe(0);
});

it('accepts a photo with the listing form and survives with the listing', function () {
    actingAsUser($this->farmer)->post('/listings', listingPayload(['photo' => fakeFile(jpegBytes())]))->assertRedirect();
    expect(ListingPhoto::count())->toBe(1);
});
