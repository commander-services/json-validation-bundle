# JSON Validation Bundle

![Test Status](https://github.com/commander/json-validation-bundle/workflows/Tests/badge.svg)

A Symfony bundle that provides an annotation to validate request/response JSON against a schema 
 using [opis/json-schema](https://github.com/opis/json-schema) validator.

### Differences from mrsuh/json-validation-bundle
* [opis/json-schema](https://github.com/opis/json-schema) validator supporting draft-06, draft-07, draft-2019-09, draft-2020-12
* supporting Symfony `5.4`

### Differences from joipolloi/json-validation-bundle
* added `response` validation
* supporting Symfony `>=3.4`, `4.*`, `5.*`
* error/warnings logging
* single validator usage

## Installation

```bash
composer require commander/json-validation-bundle
```

## Usage

Create validation schemes<br> 
See [json-schema](http://json-schema.org/) for more details<br>

JsonSchema/Request/myAction.json
```json
{
    "description": "Request JSON schema",
    "type": "object",
    "properties": {
        "test": {
            "type": "string",
            "minLength": 1
        }
    },
    "required": [ "test" ]
}
```

JsonSchema/Response/myAction.json
```json
{
    "description": "Response JSON schema",
    "type": "object",
    "properties": {
        "test": {
            "type": "string",
            "minLength": 1
        }
    },
    "required": [ "test" ]
}
```

Create controller with annotations `ValidateJsonRequest` and/or `ValidateJsonResponse`<br>
Specify `$validJson` argument if you want get decoded JSON data from the request<br>
Specify the `array` type of the `$validJson` argument if you want get decoded JSON data as `array`<br>
Specify the `object` type of the `$validJson` argument or don't specify type if you want get decoded JSON data as `object`

Controller/MyController.php
```php
<?php

use Commander\JsonValidationBundle\Annotation\ValidateJsonRequest;
use Commander\JsonValidationBundle\Annotation\ValidateJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class MyController
{
    /**
     * @ValidateJsonRequest("path/to/JsonSchema/Request/myAction.json", methods={"POST"}, emptyIsValid=true)
     * @ValidateJsonResponse("path/to/JsonSchema/Response/myAction.json", statuses={"200"}, emptyIsValid=true)
     */
    public function myAction(array $validJson): JsonResponse
    {
        return new JsonResponse($validJson);
    }
}
```

### Invalid JSON passed to request
If invalid JSON passed to request and config `enable_request_listener`, `enable_exception_listener` enabled<br>
you get response as detailed in [RFC7807](https://tools.ietf.org/html/rfc7807) with header `Content-Type:application/problem+json` and `error` log entry

```json
{
    "detail": "There was a problem with the JSON that was sent with the request",
    "errors": [
        {
            "constraint": "minLength",
            "context": 1,
            "message": "Must be at least 1 characters long",
            "minLength": 1,
            "pointer": "/test",
            "property": "test"
        }
    ],
    "status": 400,
    "title": "Unable to parse/validate JSON"
}
```

```bash
app.ERROR: Json request validation {"uri":"http://127.0.0.1:8000/my","schemaPath":"JsonSchema/Request/myAction.json","errors":[{"property":"test","pointer":"/test","message":"Must be at least 1 characters long","constraint":"minLength","context":1,"minLength":1}]} []
```

### Invalid JSON passed to response
If invalid JSON passed to response and config `enable_response_listener` enabled<br> 
you get `warning` log entry

```bash
app.WARNING: Json response validation {"uri":"http://127.0.0.1:8000/my","schemaPath":"JsonSchema/Response/myAction.json","errors":[{"property":"test","pointer":"/test","message":"Must be at least 1 characters long","constraint":"minLength","context":1,"minLength":1}]} []
```

## Configuration

```yaml
commander_json_validation:
    enable_request_listener: true #default value
    enable_response_listener: true #default value
    enable_exception_listener: true #default value
```

## Single validator usage
```php
<?php

use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyController
{
    public function myAction(Request $request, JsonValidator $validator): Response
    {
        $validator->validate($request->getContent(), 'path/to/JsonSchema/Request/myAction.json');
        $errors = $validator->getErrors();
        if(!empty($errors)) {
            // do something with errors
        }
        
        return new Response();
    }
}
```