<?php

namespace App\Repositories;

use App\Actions\VisitorParking\CalculateAmountToPayAction;
use App\Actions\VisitorParking\GetCalculationCollectionAction;
use App\Actions\VisitorParking\GetCharteredDurationAction;
use App\Actions\VisitorParking\GetCharteredPriceAction;
use App\Actions\VisitorParking\GetDiscountByPriceAction;
use App\Actions\VisitorParking\GetDiscountByTimeAction;
use App\Actions\VisitorParking\GetParkingPenaltyAction;
use App\Actions\VisitorParking\GetVisitorDurationAction;
use App\Actions\VisitorParking\RoundingUpDurationAction;
use App\Interfaces\VisitorParkingCalculatorRepositoryInterface;

class VisitorParkingCalculatorRepository implements VisitorParkingCalculatorRepositoryInterface
{
    public function calculateParking($visitorLog, $parking, $visitorParking, $is_penalty, $discount_value, $is_stamp, $vehicle_type)
    {
        $getCalculationCollectionAction = new GetCalculationCollectionAction;
        $getCalculationCollectionAction = $getCalculationCollectionAction->execute($visitorParking, $parking, $is_stamp, $vehicle_type); // id22
        $getVisitorDurationAction = new GetVisitorDurationAction;
        $getVisitorDurationAction = $getVisitorDurationAction->execute($visitorLog, $getCalculationCollectionAction); // 24435 masa bertambah
        $getCharteredDurationAction = new GetCharteredDurationAction;
        $result = $getCharteredDurationAction->execute($getVisitorDurationAction, $getCalculationCollectionAction); // 24422

        $getDiscountByTimeAction = new GetDiscountByTimeAction;
        $result = $getDiscountByTimeAction->execute($result, $discount_value, $parking); // 24424

        $roundingUpDurationAction = new RoundingUpDurationAction;
        $result = $roundingUpDurationAction->execute($result); // 408

        $calculateAmountToPayAction = new CalculateAmountToPayAction;
        $result = $calculateAmountToPayAction->execute($result, $getCalculationCollectionAction); // 8160

        $getDiscountByPriceAction = new GetDiscountByPriceAction;
        $result = $getDiscountByPriceAction->execute($result, $discount_value, $parking); // 8160

        $getCharteredPriceAction = new GetCharteredPriceAction;
        $result = $getCharteredPriceAction->execute($result, $getVisitorDurationAction, $getCalculationCollectionAction); // 8180

        $getParkingPenaltyAction = new GetParkingPenaltyAction;
        $result = $getParkingPenaltyAction->execute($result, $is_penalty, $getCalculationCollectionAction);

        return $result;
    }
}
