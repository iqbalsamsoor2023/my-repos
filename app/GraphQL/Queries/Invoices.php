<?php

namespace App\GraphQL\Queries;

use App\Actions\Invoice\CalculateTotalUnpaidBillAction;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Invoices
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
        $calculateTotalUnpaidBillAction = new CalculateTotalUnpaidBillAction;
        $total_unpaid_bill = $calculateTotalUnpaidBillAction->execute($args['user_id'], $args['payer_unit_id']);

        return [
            'total_unpaid_bill' => $total_unpaid_bill,
        ];
    }
}
