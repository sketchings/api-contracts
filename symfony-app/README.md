# Museum API — Symfony implementation

A Symfony 8.1 implementation of the [Redocly Museum API](../openapi.yaml).

## Quick start

```sh
composer install
php bin/console doctrine:migrations:migrate -n   # creates var/data_dev.db (SQLite)
php bin/console app:seed-events                  # loads the example events from the spec
symfony serve                                    # or: php -S 127.0.0.1:8000 -t public

curl -u museum:museum https://127.0.0.1:8000/museum-hours
```

## Operations

| operationId          | Route                               | Controller                          |
| -------------------- | ----------------------------------- | ----------------------------------- |
| `getMuseumHours`     | `GET /museum-hours`                 | `OperationsController`              |
| `createSpecialEvent` | `POST /special-events`              | `EventsController`                  |
| `listSpecialEvents`  | `GET /special-events`               | `EventsController`                  |
| `getSpecialEvent`    | `GET /special-events/{eventId}`     | `EventsController`                  |
| `updateSpecialEvent` | `PATCH /special-events/{eventId}`   | `EventsController`                  |
| `deleteSpecialEvent` | `DELETE /special-events/{eventId}`  | `EventsController`                  |
| `buyMuseumTickets`   | `POST /tickets`                     | `TicketsController`                 |
| `getTicketCode`      | `GET /tickets/{ticketId}/qr`        | `TicketsController`                 |
| `publishNewEvent`    | webhook (outgoing)                  | `Service\NewEventPublisher`         |

Route names match the `operationId`s (`bin/console debug:router`).

## Configuration (`.env`)

| Variable                        | Purpose                                                         |
| ------------------------------- | --------------------------------------------------------------- |
| `MUSEUM_API_USER` / `_PASSWORD` | HTTP basic credentials (`MuseumPlaceholderAuth`). Default `museum` / `museum`. |
| `PUBLISH_NEW_EVENT_WEBHOOK_URL` | Receives `publishNewEvent` when an event is created or updated. Empty disables it. |
| `DATABASE_URL`                  | Defaults to SQLite in `var/`.                                   |

## Behaviour notes

- Errors are `application/problem+json` (`type`, `title`, `status`, `detail`); validation errors add a `violations` list.
- Museum hours: Mon–Thu 09:00–18:00, Fri 10:00–16:00, closed weekends. `limit` counts opening days.
- `GET /special-events` returns all events when neither `startDate` nor `endDate` is given (the example data is from 2023). When one is given, the other gets the spec's default (`endDate` = `startDate` + 7 days; `startDate` = today).
- Event tickets require an `eventId` and a `ticketDate` that is one of the event's dates.
- Webhooks are sent on `kernel.terminate`, after the API response has gone out.

## Tests

```sh
php bin/phpunit
```

### Contract testing

[Gesso](https://studio-design.github.io/gesso/) checks every request and response in the functional tests against the repository's [`openapi.yaml`](../openapi.yaml). It runs as a PHPUnit extension (see `phpunit.dist.xml`).

- `ApiTestCase::request()` calls `assertClientMatchesOpenApiSchema()` after every request. Tests that send invalid input on purpose still pass, as long as the API answers with a 4xx response the spec documents.
- A response the spec doesn't allow (wrong type, missing required field, undocumented status) fails the test, and the failure message names the JSON pointer.
- The run ends with a coverage report showing each operation and its responses. `gesso-coverage-baseline.json` lists the responses that are allowed to stay uncovered: the 404s the spec declares but the API can't return, and the PNG QR code, whose body Gesso can't validate. Any other response going untested fails the run. After an intentional change, regenerate the baseline with `GESSO_BASELINE_GENERATE=1 php bin/phpunit`.
- Gesso doesn't enforce HTTP basic auth (`MuseumPlaceholderAuth`), so the `*RequiresBasicAuth` tests cover it.
- Check that Gesso can enforce the spec: `vendor/bin/gesso doctor --spec=../openapi.yaml`
