<?php

namespace App\GraphQL\Queries;

use App\Actions\Notification\CountUnreadNotificationAction;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Notifications
{
    /**
     * Return a value for the field.
     *
     * @param  @param  null  $root Always null, since this field has no parent.
     * @param  array{}  $args The field arguments passed by the client.
     * @param GraphQLContext $context Shared between all fields.
     * @param ResolveInfo $resolveInfo Metadata for advanced query resolution.
     * @return mixed
     */
    public function __invoke($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $countUnreadNotificationAction = new CountUnreadNotificationAction;
        $unread_notification = $countUnreadNotificationAction->execute($args['notifiable_id'], $args['unit_id'], $args['module']);

        return $unread_notification;
    }
}
