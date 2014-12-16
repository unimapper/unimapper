<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter RemoteAdapter(remote_resource)
 *
 * @property integer  $id                    m:primary
 * @property Simple[] $manyToManyNoDominance m:assoc(M<N) m:assoc-by(remoteId|simple_remote|simpleId)
 * @property string   $text
 */
class Remote extends \UniMapper\Entity
{

}