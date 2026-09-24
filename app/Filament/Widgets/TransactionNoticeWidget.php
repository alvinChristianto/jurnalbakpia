<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class TransactionNoticeWidget extends Widget
{
    use HasWidgetShield;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.transaction-notice';
}
