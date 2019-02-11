# AWS Lambda PHP Hello World

The basics of using [SAM Cli][1] for AWS Lambda PHP applications.

## Deploy
1. Install SAM Cli by following the [Quick Start][2]
2. Set up your [AWS credentials][3]
3. Select one under the options
    1. Create php binary by following steps in [`doc/create_php_binary.md`][4]
    2. Use prebuilt [Php 7.3.1][5]
    3. Just put the yml this projects ARN `arn:aws:lambda:eu-central-1:303814004728:layer:aws-lambda-layer-php-7-3-1:6`
4. Write your serverless application (!) - the default is in `src/handlers/hello.php`
5. Run `./deploy.sh` to deploy to Lambda

## PHP handler function signature
Handler: filename.functionName
    hello.hello
The signature for the PHP function is:

    function main($eventData) : array

Hello world looks like:

    <?php
    function hello($eventData) : array
    {
        return ["msg" => "Hello from PHP " . PHP_VERSION];
    }

## Local Tests
#### Input stream as event
Run:
`echo "hi" | sam local invoke HelloPhp73`
Result: 
`{"msg":"hello from PHP 7.3.1","eventData":"hi\n","data":null}`
#### File stream as event
Run:
`sam local invoke HelloPhp73 --event=events/hello.sampledata.json`
Result:
```
{
    "msg": "hello from PHP 7.3.1",
    "eventData": "{\n    \"id\": \"ulbimesm12ym12kmzkzfgkzkuzeyfg\",\n    \"name\": \"Lambda\",\n    \"icon\": \"\u03bb\"\n}",
    "data": {
        "id": "ulbimesm12ym12kmzkzfgkzkuzeyfg",
        "name": "Lambda",
        "icon": "\u03bb"
    }
}
```


[1]: https://github.com/awslabs/aws-sam-cli
[2]: https://docs.aws.amazon.com/en_us/serverless-application-model/latest/developerguide/serverless-quick-start.html
[3]: https://serverless.com/framework/docs/providers/aws/guide/credentials/
[4]: doc/create_php_binary.md
[5]: layer/php/php
[6]: 'arn:aws:lambda:eu-central-1:303814004728:layer:aws-lambda-layer-php-7-3-1:6'