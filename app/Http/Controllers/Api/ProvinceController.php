<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Erp\ThailandProvince;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    //DEPRECATED ONCE USING GRAPHQL
    /**
     * Display a listing of the provinces.
     *
     * @param  \lluminate\Http\Request  $request
     * @return Response
     */
    public function provinces(Request $request)
    {
        // TODO graphql
        $id = $request->search_id;
        $code = $request->search_code;
        $thai = $request->search_thai;
        $english = $request->search_english;

        $query = new ThailandProvince;

        if (empty($id) && empty($code) && empty($thai) && empty($english)) {
            return $query->paginate(25);
        } else {
            if (empty($request->search_id) == false) {
                $query = $query->orWhere('id', $request->search_id);
            }

            if (empty($request->search_code) == false) {
                $query = $query->orWhere('code', 'LIKE', '%'.$request->search_code.'%');
            }

            if (empty($request->search_thai) == false) {
                $query = $query->orWhere('name_in_thai', 'LIKE', '%'.$request->search_thai.'%');
            }

            if (empty($request->search_english) == false) {
                $query = $query->orWhere('name_in_english', 'LIKE', '%'.$request->search_english.'%');
            }
        }

        $model = $query->paginate(25);

        return response()->json($model);
    }
}
