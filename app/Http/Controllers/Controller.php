<?php

namespace App\Http\Controllers;

use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *   title="ClockDo Api",
     *   version="1.0",
     *   @OA\Contact(
     *     email="mamun@augnitive.com",
     *     name="Support Team"
     *   )
     * )
     */
    //Add this method to the Controller class
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => $this->item(Auth::user(), new UserTransformer())
        ], 200);
    }


    /**
     * Create the response for an item.
     *
     * @param mixed $item
     * @param TransformerAbstract $transformer
     * @return JsonResponse
     */
    protected function item($item, TransformerAbstract $transformer)
    {
        $resource = new Item($item, $transformer);

        return $this->buildResourceResponse($resource)->original['data'];
    }

    /**
     * Create the response for a collection.
     *
     * @param mixed $collection
     * @param TransformerAbstract $transformer
     * @return JsonResponse
     */
    protected function collection($collection, TransformerAbstract $transformer)
    {
        $resource = new Collection($collection, $transformer);

        return $this->buildResourceResponse($resource)->original['data'];
    }

    /**
     * Create the response for a resource.
     *
     * @param ResourceAbstract $resource
     * @return JsonResponse
     */
    protected function buildResourceResponse(ResourceAbstract $resource)
    {
        $fractal = app('League\Fractal\Manager');

        return response()->json(
            $fractal->createData($resource)->toArray()
        );
    }
}
