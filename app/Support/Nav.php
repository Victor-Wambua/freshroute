<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

class Nav
{
    /** @return array{tabs: list<array>, side: list<array>} item = [route, icon, label key, patterns, cta?] */
    public static function for(User $u): array
    {
        $i = fn ($route, $icon, $label, $pat = null, $cta = false) => ['route' => $route, 'icon' => $icon, 'label' => $label, 'pat' => $pat ?? [$route], 'cta' => $cta];
        $more = $i('more', 'menu', 'nav_more', ['more', 'my-data.*', 'farms.*', 'password.*']);
        $alerts = $i('alerts.index', 'bell', 'nav_alerts', ['alerts.*']);
        $prices = $i('prices.index', 'chart', 'nav_prices', ['prices.*']);

        return match ($u->role) {
            Role::Farmer => [
                'tabs' => [$i('home', 'home', 'nav_home'), $i('listings.create', 'plus', 'nav_sell', ['listings.create'], true), $i('calendar.index', 'cal', 'nav_harvest', ['calendar.*']), $i('losses.index', 'loss', 'nav_losses', ['losses.*']), $more],
                'side' => [$i('home', 'home', 'nav_home'), $i('listings.create', 'plus', 'nav_sell', ['listings.create']), $i('listings.index', 'box', 'nav_my_produce', ['listings.index', 'listings.show', 'listings.edit']), $i('calendar.index', 'cal', 'nav_harvest', ['calendar.*']), $i('losses.index', 'loss', 'nav_losses', ['losses.*']), $i('enquiries.index', 'msg', 'nav_enquiries', ['enquiries.*']), $i('deliveries.index', 'truck', 'nav_deliveries', ['deliveries.*']), $i('storage.index', 'store', 'nav_storage', ['storage.index']), $i('transport.index', 'truck', 'nav_transport', ['transport.index']), $prices, $i('farms.index', 'pin', 'nav_farm', ['farms.*']), $alerts, $i('my-data.index', 'download', 'nav_mydata', ['my-data.*'])],
            ],
            Role::Buyer => [
                'tabs' => [$i('market.index', 'search', 'nav_market', ['market.*']), $i('saved.index', 'heart', 'nav_saved', ['saved.*']), $i('enquiries.index', 'msg', 'nav_enquiries', ['enquiries.*']), $prices, $more],
                'side' => [$i('market.index', 'search', 'nav_market', ['market.*']), $i('needs.index', 'tag', 'nav_needs', ['needs.*']), $i('saved.index', 'heart', 'nav_saved', ['saved.*']), $i('enquiries.index', 'msg', 'nav_enquiries', ['enquiries.*']), $i('deliveries.index', 'truck', 'nav_deliveries', ['deliveries.*']), $i('storage.index', 'store', 'nav_storage', ['storage.index']), $i('transport.index', 'truck', 'nav_transport', ['transport.index']), $prices, $alerts, $i('my-data.index', 'download', 'nav_mydata', ['my-data.*'])],
            ],
            Role::StorageProvider => [
                'tabs' => [$i('storage.mine', 'store', 'nav_my_storage', ['storage.mine', 'storage.create', 'storage.edit']), $i('storage.requests', 'msg', 'nav_requests', ['storage.requests']), $prices, $alerts, $more],
                'side' => [$i('storage.mine', 'store', 'nav_my_storage', ['storage.mine', 'storage.create', 'storage.edit']), $i('storage.requests', 'msg', 'nav_requests', ['storage.requests']), $i('storage.index', 'search', 'nav_storage', ['storage.index']), $prices, $alerts, $i('my-data.index', 'download', 'nav_mydata', ['my-data.*'])],
            ],
            Role::Transporter => [
                'tabs' => [$i('transport.profile', 'truck', 'nav_my_vehicle', ['transport.profile']), $i('deliveries.index', 'box', 'nav_deliveries', ['deliveries.*']), $prices, $alerts, $more],
                'side' => [$i('transport.profile', 'truck', 'nav_my_vehicle', ['transport.profile']), $i('deliveries.index', 'box', 'nav_deliveries', ['deliveries.*']), $i('transport.index', 'search', 'nav_transport', ['transport.index']), $prices, $alerts, $i('my-data.index', 'download', 'nav_mydata', ['my-data.*'])],
            ],
            Role::CoopAdmin => [
                'tabs' => [$i('coop.index', 'chart', 'nav_overview', ['coop.index']), $i('coop.members', 'users', 'nav_members', ['coop.members']), $i('coop.reports', 'loss', 'nav_reports', ['coop.reports']), $prices, $more],
                'side' => [$i('coop.index', 'chart', 'nav_overview', ['coop.index']), $i('coop.members', 'users', 'nav_members', ['coop.members']), $i('coop.reports', 'loss', 'nav_reports', ['coop.reports']), $prices, $alerts, $i('mfa.setup', 'user', 'nav_security', ['mfa.*']), $i('my-data.index', 'download', 'nav_mydata', ['my-data.*'])],
            ],
            Role::PlatformAdmin => [
                'tabs' => [$i('admin.index', 'chart', 'nav_overview', ['admin.index']), $i('admin.users', 'users', 'nav_users', ['admin.users']), $i('admin.audit', 'msg', 'nav_audit', ['admin.audit']), $i('admin.prices', 'tag', 'nav_prices', ['admin.prices']), $more],
                'side' => [$i('admin.index', 'chart', 'nav_overview', ['admin.index']), $i('admin.users', 'users', 'nav_users', ['admin.users']), $i('admin.verify', 'check', 'nav_verify', ['admin.verify']), $i('admin.audit', 'msg', 'nav_audit', ['admin.audit']), $i('admin.deletions', 'x', 'nav_deletions', ['admin.deletions']), $i('admin.prices', 'tag', 'nav_prices', ['admin.prices']), $i('prices.index', 'chart', 'nav_price_view', ['prices.*'])],
            ],
        };
    }

    /** Links shown on the "More" page: everything in the side nav that is not in the tab bar. */
    public static function more(User $u): array
    {
        $n = self::for($u);
        $tabRoutes = array_column($n['tabs'], 'route');

        return array_values(array_filter($n['side'], fn ($s) => ! in_array($s['route'], $tabRoutes, true)));
    }
}
