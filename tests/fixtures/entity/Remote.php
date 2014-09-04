<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter RemoteAdapter(remote_resource)
 *
 * @property integer  $id                 m:primary
 * @property Simple[] $hasManyNoDominance m:assoc(M<N=remoteId|simple_remote|simpleId)
 */
class Remote extends \UniMapper\Entity
{

}