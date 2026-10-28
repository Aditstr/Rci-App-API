<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClientWalletPageTest extends TestCase
{
    public function test_wallet_page_renders_with_a_link_back_to_membership(): void
    {
        $this->get('/client/wallet')
            ->assertOk()
            ->assertSee('/client/membership', false);
    }

    public function test_membership_page_is_served_by_the_spa(): void
    {
        $this->get('/client/membership')->assertOk();
    }
}
