<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MembershipMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function walletMigration(): object
    {
        return require database_path(
            'migrations/2026_10_25_000001_allow_subscription_payment_wallet_transaction_type.php'
        );
    }

    private function walletTableSql(): string
    {
        return (string) DB::table('sqlite_master')
            ->where('name', 'wallet_transactions')
            ->value('sql');
    }

    private function walletIndexNames(): array
    {
        return array_map(
            fn ($index) => $index->name,
            DB::select("PRAGMA index_list('wallet_transactions')"),
        );
    }

    private function assertWalletSchemaIntact(bool $allowsSubscriptionPayment): void
    {
        $sql = $this->walletTableSql();

        if ($allowsSubscriptionPayment) {
            $this->assertStringContainsString("'subscription_payment'", $sql);
        } else {
            $this->assertStringNotContainsString("'subscription_payment'", $sql);
        }

        $this->assertStringContainsString(
            "check (\"status\" in ('pending', 'success', 'failed'))",
            $sql,
        );
        $this->assertStringContainsString(
            'foreign key("wallet_id") references wallets("id")',
            $sql,
        );

        $indexes = $this->walletIndexNames();
        $this->assertContains('wallet_transactions_type_index', $indexes);
        $this->assertContains('wallet_transactions_status_index', $indexes);
        $this->assertContains('wallet_transactions_reference_type_reference_id_index', $indexes);
    }

    private function createWalletTransaction(string $type): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => '0.00']);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => '10000.00',
            'type' => $type,
            'status' => 'success',
        ]);
    }

    public function test_wallet_schema_keeps_constraints_and_indexes(): void
    {
        $this->assertWalletSchemaIntact(true);
    }

    public function test_wallet_migration_can_run_again_on_an_updated_schema(): void
    {
        $this->createWalletTransaction('deposit');

        $this->walletMigration()->up();

        $this->assertWalletSchemaIntact(true);
        $this->assertSame(1, WalletTransaction::where('type', 'deposit')->count());
    }

    public function test_wallet_rollback_stops_when_subscription_payment_rows_exist(): void
    {
        $this->createWalletTransaction('subscription_payment');

        try {
            $this->walletMigration()->down();
            $this->fail('Rollback should have thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('subscription_payment', $e->getMessage());
        }

        $this->assertWalletSchemaIntact(true);
        $this->assertSame(
            1,
            WalletTransaction::where('type', 'subscription_payment')->count(),
        );
    }

    public function test_wallet_rollback_removes_only_the_type_and_keeps_other_schema(): void
    {
        $this->createWalletTransaction('deposit');

        $this->walletMigration()->down();

        $this->assertWalletSchemaIntact(false);
        $this->assertSame(1, WalletTransaction::where('type', 'deposit')->count());

        $this->walletMigration()->up();

        $this->assertWalletSchemaIntact(true);
    }

    public function test_postgres_rollback_returns_before_checking_membership_rows(): void
    {
        $source = file_get_contents(database_path(
            'migrations/2026_10_25_000001_allow_subscription_payment_wallet_transaction_type.php'
        ));

        $down = substr($source, strpos($source, 'public function down'));

        $pgsqlCheck = strpos($down, "getDriverName() === 'pgsql'");
        $membershipCheck = strpos($down, "where('type', 'subscription_payment')");

        $this->assertNotFalse($pgsqlCheck);
        $this->assertNotFalse($membershipCheck);
        $this->assertLessThan($membershipCheck, $pgsqlCheck);
    }

    public function test_subscriptions_have_a_unique_user_idempotency_key_index(): void
    {
        $index = collect(DB::select("PRAGMA index_list('subscriptions')"))
            ->firstWhere('name', 'subscriptions_user_id_idempotency_key_unique');

        $this->assertNotNull($index);
        $this->assertSame(1, (int) $index->unique);
    }
}
