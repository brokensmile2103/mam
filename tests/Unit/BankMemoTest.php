<?php

namespace Tests\Unit;

use App\Models\Center;
use PHPUnit\Framework\TestCase;

class BankMemoTest extends TestCase
{
    public function test_default_template_gives_the_original_memo(): void
    {
        $this->assertSame('HP 2610 TRAN GIA BAO', Center::renderMemo(Center::DEFAULT_BANK_MEMO, ['month' => '2610', 'child' => 'TRAN GIA BAO']));
    }

    public function test_empty_template_gives_empty_memo(): void
    {
        $this->assertSame('', Center::renderMemo('', ['month' => '2610', 'child' => 'TRAN GIA BAO']));
    }

    public function test_custom_template_and_cleanup(): void
    {
        $this->assertSame('Hoc phi 2610', Center::renderMemo('Học phí {month}', ['month' => '2610', 'child' => 'A']));
        $this->assertSame('HP {month}', Center::cleanMemoTemplate('HP {month}#'));
    }
}
