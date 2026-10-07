<?php

namespace Tests\Unit;

use App\Support\LegacyIsiLabel;
use Tests\TestCase;

class LegacyIsiLabelTest extends TestCase
{
    public function test_it_labels_every_historical_variant_shape(): void
    {
        $this->assertSame('isi 8', LegacyIsiLabel::forVariant('box_8'));
        $this->assertSame('isi 8', LegacyIsiLabel::forVariant('8'));
        $this->assertSame('isi 8', LegacyIsiLabel::forVariant(8));
        $this->assertSame('isi 18', LegacyIsiLabel::forVariant('box_18'));
        $this->assertSame('isi 18', LegacyIsiLabel::forVariant('18'));
        $this->assertSame('isi 18', LegacyIsiLabel::forVariant(18));
    }

    public function test_it_returns_null_for_lines_without_a_variant(): void
    {
        $this->assertNull(LegacyIsiLabel::forVariant(null));
        $this->assertNull(LegacyIsiLabel::forVariant('box_12'));
        $this->assertNull(LegacyIsiLabel::forVariant(12));
    }

    public function test_it_attaches_the_label_to_a_legacy_line(): void
    {
        $line = LegacyIsiLabel::apply((object) ['box_varian' => 'box_18', 'amount' => 3]);

        $this->assertSame('isi 18', $line->isi);
        $this->assertSame('box_18', $line->box_varian);
    }

    public function test_it_leaves_a_new_line_unlabelled(): void
    {
        $line = LegacyIsiLabel::apply((object) ['product_name' => 'Bakpia Keju', 'amount' => 2]);

        $this->assertObjectNotHasProperty('isi', $line);
        $this->assertSame('Bakpia Keju', $line->product_name);
    }

    public function test_it_labels_only_the_legacy_lines_of_a_mixed_transaction(): void
    {
        $lines = LegacyIsiLabel::applyAll([
            (object) ['box_varian' => '8', 'amount' => 2],
            (object) ['product_name' => 'Bakpia Keju', 'amount' => 5],
            (object) ['box_varian' => 'box_18', 'amount' => 1],
        ]);

        $this->assertSame('isi 8', $lines[0]->isi);
        $this->assertObjectNotHasProperty('isi', $lines[1]);
        $this->assertSame('isi 18', $lines[2]->isi);
    }
}
