<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Psr\Http\Message\ResponseInterface;
use \Illuminate\Http\JsonResponse;
use App\Models\WeatherStation;
use GuzzleHttp;
use JSON\Attributes\JSON;
use JSON\Unmarshal;

/**
 * @OA\Info(
 *     title="BOM Weather Swagger",
 *     version="1.0.0",
 * )
 *
 * @OA\get(
 *     path="/api/current_temperature/{weatherStationId}/",
 *     summary="Get current temperature at a BoM weather station",
 *     description="Get current temperature at a BoM weather station",
 *     operationId="currentTemperature",
 *     tags={"weather"},
 *     @OA\Parameter(
 *         description="ID of weather station",
 *         in="path",
 *         name="weatherStationId",
 *         required=true,
 *         example="1",
 *         @OA\Schema(
 *             type="integer",
 *             format="int64"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/currentTemperatureGet")
 *         )
 *     )
 * )
 *
 * @OA\get(
 *     path="/api/weather_stations/",
 *     summary="Get list of weather stations that weather can be checked for",
 *     description="Get list of weather stations that weather can be checked for",
 *     operationId="listWeatherStations",
 *     tags={"weather"},
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/listWeatherStationsGet")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="currentTemperatureGet",
 *     title="Result of GET currentTemperature",
 * 	   @OA\Property(
 * 	       property="status",
 * 		   type="string"
 * 	   ),
 * 	   @OA\Property(
 * 	      property="error",
 * 		  type="string"
 * 	   )
 * )
 *
 * @OA\Schema(
 *     schema="listWeatherStationsGet",
 *     title="Result of GET listWeatherStations",
 *     @OA\Property(
 * 	       property="status",
 * 	       type="string"
 * 	   ),
 * 	   @OA\Property(
 * 	       property="error",
 * 	       type="string"
 * 	   )
 * )
 */
class WeatherController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Show the current weather at a given weather station
     *
     * @param  int  $id
     * @return Response
     */
    public function getCurrentTemperature(int $weatherStationId)
    {
        $client = new GuzzleHttp\Client();
        $weatherStation = WeatherStation::findOrFail($weatherStationId);

        $rawResponse = $client->request(
            'GET',
            sprintf(
                'http://reg.bom.gov.au/fwo/%1$s/%1$s.%2$s.json',
                $weatherStation->bom_city_code,
                $weatherStation->bom_station_code
            )
        );

        $apiProxy = new WeatherStationCurrentTemperatureAPIProxy($rawResponse);
        return $apiProxy->client_response();
    }

    /**
     * Show a list of weather stations
     *
     * @return Response
     */
    public function listWeatherStations()
    {
        return WeatherStation::all();
    }
}

interface UnmarshalledUpstream {}

/**
 * @property UnmarshalledUpstream $unmarshalledUpstream
 */
interface APIProxy {
    public function __construct(ResponseInterface $upstreamHttpResp);
    function unmarshall_http_response(
        ResponseInterface $upstreamHttpResp,
        UnmarshalledUpstream $unmarshalledUpstream
    ): UnmarshalledUpstream;
    public function client_response(): JsonResponse;
}

trait UnmarshallHTTPResponseTrait {
    function unmarshall_http_response(ResponseInterface $upstreamHttpResp, UnmarshalledUpstream $unmarshalledUpstream): UnmarshalledUpstream {
        $jsonResponse = \GuzzleHttp\json_decode($upstreamHttpResp->getBody(), true);
        Unmarshal::decode($unmarshalledUpstream, $jsonResponse['observations']);
        return $unmarshalledUpstream;
    }
}

class WeatherStationObservations implements UnmarshalledUpstream {
    #[JSON(field: 'header', type: WeatherStationObservationHeader::class)]
    public array $header;
    #[JSON(field: 'data', type: WeatherStationObservationData::class)]
    public array $data;
}

class WeatherStationObservationHeader {
    #[JSON('name')]
    public string $name;
    #[JSON('state')]
    public string $state;
}

class WeatherStationObservationData {
    #[JSON('local_date_time')]
    public string $localDateTime;
    #[JSON('air_temp')]
    public string $airTemp;
}

class WeatherStationCurrentTemperatureAPIProxy implements APIProxy {
    use UnmarshallHTTPResponseTrait;
    public UnmarshalledUpstream $unmarshalledUpstream;

    public function __construct(ResponseInterface $upstreamHttpResp) {
        $this->unmarshalledUpstream = $this->unmarshall_http_response($upstreamHttpResp, new WeatherStationObservations());
    }

    public function client_response(): JsonResponse {
        return response()->json(
            array(
                "city" => $this->unmarshalledUpstream->header[0]->name,
                "state" => $this->unmarshalledUpstream->header[0]->state,
                "measured" => $this->unmarshalledUpstream->data[0]->localDateTime,
                "temperature" => $this->unmarshalledUpstream->data[0]->airTemp,
            ),
            200
        );
    }
}
