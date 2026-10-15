# Workshop exercises

Four hands-on exercises for the API contracts tutorial. They all use the Museum API: the spec in `spec/` (bundled to `openapi.yaml`) and the Symfony app in `symfony-app/`.

## Setup

```sh
npm install
cd symfony-app && composer install && cd ..
symfony serve -d --dir=symfony-app      # https://localhost:8000
make arazzo-db-reset                    # create and seed the database
```

Exercises 2 and 3 call the running app over HTTPS. If Node rejects the certificate, run `symfony server:ca:install` once.

The API uses HTTP basic auth: `museum` / `museum`. The Makefile passes these to Respect and Schemathesis as `MUSEUM_API_USER` and `MUSEUM_API_PASSWORD`.

---

## 1. Fix the broken spec

1. Check out the branch `exercise/broken-spec`.
2. Run `make openapi-lint` and read the errors. It lints the source files in `spec/`.
3. Fix the undefined `$ref`. The error gives the file and line.
4. Add the missing `operationId`. Lint reports it as a **warning** (`operation-operationId`), not an error, so read past the errors.
5. Document the 404 the endpoint returns. Lint can't find this one:
   - Run `make openapi-bundle` (it only works once lint passes), then `make app-tests`.
     - Gesso fails with `Status code 404 not defined for GET /special-events/{eventId}`.
             - You can confirm it with `curl -k -u museum:museum https://localhost:8000/special-events/00000000-0000-4000-8000-000000000000`.

**Done when** `make openapi-lint` and `make app-tests` both pass, and `git diff longhornphp2026start -- spec` shows no differences.

## 2. Write a workflow

1. Open the starter file `exercises/special-events.arazzo.yaml`. Auth is already set up, and `list-events` is a finished step you can copy.
2. Add a `create-event` step whose output is the new `eventId`.
3. Add a `get-event` step that fetches the event using `$steps.create-event.outputs.eventId`.
4. Add an `update-event` step that changes the price and checks the new value with a jsonpath criterion: `$[?(@.price == 15)]`.
5. Run `make respect FILE=exercises/special-events.arazzo.yaml`.
6. Break one criterion, for example expect `99`, then run it again and read the failure.

A JSONPath criterion must be a filter such as `$[?(@.name == 'x')]`. `$.name == 'x'` always fails.

`make respect` resets the database afterwards. The solution is in `exercises/solutions/special-events.arazzo.yaml`.

## 3. Fix a real bug

1. Run `make schemathesis`. Add `SCHEMATHESIS_ARGS="--seed 1"` if the whole room should see the same failures.
2. Pick one failure and reproduce it with the `curl` command Schemathesis prints. Replace `-H 'Authorization: [Filtered]'` with `-u museum:museum`.
3. Decide whether the code or the spec is wrong. Some examples:
   - **Undocumented 401** → spec. Every operation requires auth.
   - **Server error (500)** → code.
   - **API rejected a valid request** → usually the spec is missing a constraint the code enforces.
4. Fix it, in `spec/` (then `make openapi-bundle`) or in `symfony-app/`.
5. Rerun just that operation: `make schemathesis SCHEMATHESIS_ARGS="--include-operation-id <operationId>"`.

Reference fixes for every failure are on the branch `longhorn2026schemathesisupdate`.

## 4. Break the contract

1. Pick any controller in `symfony-app/src/Controller/`. Responses are built by the `toArray()` methods in `symfony-app/src/Entity/`.
2. Change a field name or type in its response. For example, in `SpecialEvent::toArray()` change `'price' => $this->price` to `'price' => (string) $this->price`.
3. Run the tests from the app folder: `cd symfony-app && vendor/bin/phpunit`. `make app-tests` from the repo root does the same.
4. Find the schema path in the failure output, for example `[/price] The data (string) must match the type: number`. In a list response the path includes the index: `[/0/price]`.
   - **If you renamed a field instead**, the failure reports the field that is now missing: `[/] The required properties (name) are missing`. The new name appears in the "Strict Additional Properties" warning at the end.
5. Revert the change (`git checkout -- src/`) and confirm the tests are green again.
