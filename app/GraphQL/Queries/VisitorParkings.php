<?php

namespace App\GraphQL\Queries;

use App\Actions\VisitorParking\GetCalculationCollectionAction;
use App\Actions\VisitorParking\GetVisitorDurationAction;
use App\Interfaces\VisitorParkingCalculatorRepositoryInterface;
use App\Models\Parking;
use App\Models\VisitorLog;
use App\Models\VisitorParking;
use Carbon\CarbonInterval;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class VisitorParkings
{
    public function __construct(VisitorParkingCalculatorRepositoryInterface $visitorParkingCalculator)
    {
        $this->visitorParkingCalculator = $visitorParkingCalculator;
    }

    /**
     * Return a value for the field.
     *
     * @param  null  $root Always null, since this field has no parent.
     * @param  array{}  $args The field arguments passed by the client.
     * @param GraphQLContext $context Shared between all fields.
     * @param ResolveInfo $resolveInfo Metadata for advanced query resolution.
     * @return mixed
     */
    public function __invoke($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $visitorLog = VisitorLog::findOrFail($args['visitor_log_id']);
        $parking = Parking::where('residence_id', '=', $args['residence_id'])->first();
        $visitorParking = VisitorParking::where('visitor_log_id', $args['visitor_log_id'])->first();

        $amount_to_pay = $this->visitorParkingCalculator->calculateParking(
            $visitorLog,
            $parking,
            $visitorParking,
            $args['is_penalty'],
            $args['discount_value'],
            $args['is_stamp'],
            $args['vehicle_type']
        );

        $getCalculationCollectionAction = new GetCalculationCollectionAction;
        $getCalculationCollectionAction = $getCalculationCollectionAction->execute($visitorParking, $parking, $args['is_stamp'], $args['vehicle_type']);

        $getVisitorDurationAction = new GetVisitorDurationAction;
        $getVisitorDurationAction = $getVisitorDurationAction->execute($visitorLog, $getCalculationCollectionAction);

        return [
            'duration' => CarbonInterval::minutes($getVisitorDurationAction)->cascade()->forHumans(),
            'parking_fee_total' => $amount_to_pay.' THB',
        ];
    }
}
