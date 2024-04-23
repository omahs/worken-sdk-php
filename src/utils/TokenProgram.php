<?php

namespace Worken\Utils;

use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;

class TokenProgram
{
    const TRANSFER_INDEX = 3; // Transfer SPL instruction index

    public static function transfer(
        PublicKey $fromPubkey,
        PublicKey $toPublicKey,
        PublicKey $mintPublicKey,
        PublicKey $authorityPubkey,
        int $amount
    ): TransactionInstruction
    {
        $data = pack('C', self::TRANSFER_INDEX) . pack('V', $amount);

        $keys = [
            new AccountMeta($fromPubkey, true, true), // From account (signer)
            new AccountMeta($toPublicKey, false, true), // To account
            new AccountMeta($mintPublicKey, false, false), // Mint account
            new AccountMeta($authorityPubkey, false, true), // Authority account (same as from account)
        ];

        return new TransactionInstruction(
            self::tokenProgramId(),
            $keys,
            $data
        );
    }

    public static function tokenProgramId()
    {
        return new PublicKey("TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA"); // Token program ID
    }
}
