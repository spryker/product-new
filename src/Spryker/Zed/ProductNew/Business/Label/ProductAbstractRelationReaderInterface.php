<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductNew\Business\Label;

interface ProductAbstractRelationReaderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ProductLabelProductAbstractRelationsTransfer>
     */
    public function findProductLabelProductAbstractRelationChanges();
}
