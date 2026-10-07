<?php

namespace App\GraphQL\Queries;

use App\Models\EventRsvp;
use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class UpdateIsGoingEventRsvp
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
        try {
            $eventID = $args['event_id'] ?? null;
            $userID = $args['user_id'] ?? null;
            $isGoing = (int) $args['is_going'] ?? null;

            if (! ($eventID || $userID || $isGoing)) {
                return 0;
            }

            // update event rsvp
            EventRsvp::where([
                'event_id' => $eventID,
                'user_id' => $userID,
            ])->update([
                'is_going' => $isGoing,
            ]);

            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }
}
