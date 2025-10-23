<?php

declare(strict_types=1);

namespace Inpsyde\WcProductContracts;

interface ProductState
{
    public const NEW = 'new';

    public const AUTO_DRAFT = 'auto-draft';

    public const DRAFT = 'draft';

    public const PUBLISH = 'publish';

    public const PENDING = 'pending';

    public const FUTURE = 'future';

    public const PRIVATE = 'private';

    public const INHERIT = 'inherit';

    public const TRASH = 'trash';
}
