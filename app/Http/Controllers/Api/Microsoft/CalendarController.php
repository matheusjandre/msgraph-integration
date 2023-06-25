<?php

namespace App\Http\Controllers\Api\Microsoft;

use App\Http\Controllers\Controller;
use App\Models\User;
use Beta\Microsoft\Graph\Model\Event;
use DateTimeZone;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends Controller
{
    /**
     * Lista os eventos do calendario do usuario no Microsoft Graph
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        $graph = $this->getGraph($user);

        if (empty($graph))
            response()->json(
                [
                    'status' => 'You dont have permission to use this feature.'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );

        // Set user's timezone
        $string_tz = "America/Sao_Paulo";
        $timezone = new DateTimeZone($string_tz);

        // Pega inicio de final da semana.
        $start = new \DateTimeImmutable('sunday -1 week', $timezone);
        $end = new \DateTimeImmutable('sunday', $timezone);

        $data['dateRange'] = $start->format('j/m/Y') . ' - ' . $end->format('j/m/Y');

        // Parametros do Microsoft Graph
        $queryParams = array(
            'startDateTime' => $start->format(\DateTime::ATOM),
            'endDateTime' => $end->format(\DateTime::ATOM),
            '$select' => 'subject,organizer,start,end',
            '$orderby' => 'start/dateTime',
            '$top' => 25
        );

        // adiciona parametros ao '/me/calendarView'
        $getEventsUrl = '/me/calendarView?' . http_build_query($queryParams);

        $data['events'] = $graph->createRequest('GET', $getEventsUrl)
            ->addHeaders(array(
                'Prefer' => 'outlook.timezone="' . $string_tz . '"'
            ))
            ->setReturnType(Event::class)
            ->execute();

        // Retorna os dados ao usuario.
        return response()->json(
            [
                'data' => $data
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Cria novo evento no calendario do usuario no Microsoft Graph
     */
    public function newEvent(Request $request)
    {
        $request->validate([
            'eventSubject' => 'nullable|string',
            'eventAttendees' => 'nullable|string',
            'eventStart' => 'required|date',
            'eventEnd' => 'required|date',
            'eventBody' => 'nullable|string'
        ]);

        $user = $request->user();
        $graph = $this->getGraph($user);

        if (empty($graph))
            response()->json(
                [
                    'status' => 'You dont have permission to use this feature.'
                ],
                Response::HTTP_NOT_ACCEPTABLE
            );

        // Endereco de email dos participantes, separados por ;
        $attendeeAddresses = explode(';', $request->eventAttendees);

        // Configura o objeto de participantes do evento.
        $attendees = [];
        foreach ($attendeeAddresses as $attendeeAddress) {
            array_push($attendees, [
                'emailAddress' => [
                    'address' => $attendeeAddress
                ],
                'type' => 'required'
            ]);
        }

        // Cria o evento.
        $newEvent = [
            'subject' => $request->eventSubject,
            'attendees' => $attendees,
            'start' => [
                'dateTime' => $request->eventStart,
                'timeZone' => "America/Sao_Paulo"
            ],
            'end' => [
                'dateTime' => $request->eventEnd,
                'timeZone' => "America/Sao_Paulo"
            ],
            'body' => [
                'content' => $request->eventBody,
                'contentType' => 'text'
            ]
        ];

        // Encaminha ao Microsoft Graph
        $response = $graph->createRequest('POST', '/me/events')
            ->attachBody($newEvent)
            ->setReturnType(Event::class)
            ->execute();

        return response()->json(
            [
                'response' => $response
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Configura a classe Graph para ser utilizada com o token do usuario.
     */
    private function getGraph(?User $user): ?Graph
    {
        if (!$user || empty($user->ms_token))
            return null;

        $graph = new Graph();
        $graph->setAccessToken($user->ms_token);

        return $graph;
    }
}
