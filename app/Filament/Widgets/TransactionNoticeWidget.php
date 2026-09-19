<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TransactionNoticeWidget extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.transaction-notice';
}
