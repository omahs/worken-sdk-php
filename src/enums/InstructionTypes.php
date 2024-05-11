<?php

namespace Worken\Enums;

enum InstructionTypes: int {
    case InitializeMint = 0;
    case InitializeAccount = 1;
    case InitializeMultisig = 2;
    case Transfer = 3;
    case Approve = 4;
    case Revoke = 5;
    case SetAuthority = 6;
    case MintTo = 7;
    case Burn = 8;
    case CloseAccount = 9;
    case FreezeAccount = 10;
    case ThawAccount = 11;
    case TransferChecked = 12;
    case ApproveChecked = 13;
    case MintToChecked = 14;
    case BurnChecked = 15;
    case InitializeAccount2 = 16;
    case SyncNative = 17;
    case InitializeAccount3 = 18;
    case InitializeMultisig2 = 19;
    case InitializeMint2 = 20;
    case GetAccountDataSize = 21;
    case InitializeImmutableOwner = 22;
    case AmountToUiAmount = 23;
    case UiAmountToAmount = 24;
    case InitializeMintCloseAuthority = 25;
    case TransferFeeExtension = 26;
    case ConfidentialTransferExtension = 27;
    case DefaultAccountStateExtension = 28;
    case Reallocate = 29;
    case MemoTransferExtension = 30;
    case CreateNativeMint = 31;
    case InitializeNonTransferableMint = 32;
    case InterestBearingMintExtension = 33;
    case CpiGuardExtension = 34;
    case InitializePermanentDelegate = 35;
    case TransferHookExtension = 36;
    //case ConfidentialTransferFeeExtension = 37;
    //case WithdrawalExcessLamports = 38;
    case MetadataPointerExtension = 39;
    case GroupPointerExtension = 40;
    case GroupMemberPointerExtension = 41;
};
