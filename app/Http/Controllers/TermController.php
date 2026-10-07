<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Response;
use App\Actions\ResourceMaterial\GetOneStaticContentAction;
use App\Enums\ResourceMaterial\ResourceTypeEnum;
use Illuminate\Http\Request;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        try {
            $getOneStaticContentAction = new GetOneStaticContentAction;
            $request = new Request([
                'type' => ResourceTypeEnum::TERMS_OF_SERVICE->value,
            ]);
            $staticContent = $getOneStaticContentAction->execute($request);

            $content = $staticContent->getTranslation('content', 'en');

            return view('terms.index', [
                'content' => $content,
            ]);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()]);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function indexTh()
    {
        try {
            $getOneStaticContentAction = new GetOneStaticContentAction;
            $request = new Request([
                'type' => ResourceTypeEnum::TERMS_OF_SERVICE->value,
            ]);
            $staticContent = $getOneStaticContentAction->execute($request);

            $content = $staticContent->getTranslation('content', 'th');

            return view('terms.index-th', [
                'content' => $content,
            ]);
        } catch (Exception $exception) {
            return $this->logServerError($exception);
        }
    }
}
