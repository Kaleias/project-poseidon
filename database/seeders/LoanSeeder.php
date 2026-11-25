<?php

namespace Database\Seeders;

use App\Actions\Loans\CreateLoanAction;
use App\Actions\Loans\ReturnLoanAction;
use App\Models\Book;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Seed loans through the Action classes so book stock stays consistent
     * with the loan records (available_quantity is reduced for active loans).
     */
    public function run(CreateLoanAction $create, ReturnLoanAction $return): void
    {
        $issuer = User::where('role', 'librarian')->first() ?? User::first();
        $members = Member::orderBy('id')->get();
        $books = Book::orderBy('id')->get();

        if ($members->isEmpty() || $books->isEmpty()) {
            return;
        }

        // 6 active loans (due in 14 days)
        for ($i = 0; $i < 6; $i++) {
            $create->execute([
                'book_id' => $books[$i % $books->count()]->id,
                'member_id' => $members[$i % $members->count()]->id,
                'borrowed_at' => today()->subDays($i)->toDateString(),
                'due_date' => today()->subDays($i)->addDays(14)->toDateString(),
                'notes' => null,
            ], $issuer);
        }

        // 2 overdue loans
        for ($i = 6; $i < 8; $i++) {
            $create->execute([
                'book_id' => $books[$i % $books->count()]->id,
                'member_id' => $members[$i % $members->count()]->id,
                'borrowed_at' => today()->subDays(30)->toDateString(),
                'due_date' => today()->subDays(16)->toDateString(),
                'notes' => 'Reminder sent to member.',
            ], $issuer);
        }

        // 4 returned loans (history)
        for ($i = 8; $i < 12; $i++) {
            $loan = $create->execute([
                'book_id' => $books[$i % $books->count()]->id,
                'member_id' => $members[$i % $members->count()]->id,
                'borrowed_at' => today()->subDays(25)->toDateString(),
                'due_date' => today()->subDays(11)->toDateString(),
                'notes' => null,
            ], $issuer);

            $return->execute($loan);
        }
    }
}
