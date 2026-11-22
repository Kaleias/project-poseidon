<?php

namespace App\Actions\Members;

use App\Models\Member;

class DeleteMemberAction
{
    public function execute(Member $member): bool
    {
        return $member->delete();
    }
}
