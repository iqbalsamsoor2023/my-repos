<?php

namespace App\GraphQL\Queries;

use App\Enums\Visitor\Feedback;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Feedbacks
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
        return [
            'remember_wrong' => Feedback::REMEMBER_WRONG->value,
            'remember_wrong_th' => Feedback::REMEMBER_WRONG_TH->value,
            'press_wrong' => Feedback::PRESS_WRONG->value,
            'press_wrong_th' => Feedback::PRESS_WRONG_TH->value,
            'impersonation' => Feedback::IMPERSONATION->value,
            'impersonation_th' => Feedback::IMPERSONATION_TH->value,
            'other' => Feedback::OTHER->value,
            'other_th' => Feedback::OTEHR_TH->value,
        ];
    }
}
