#!/bin/bash

sam package --template-file template.yaml --output-template-file serverless-output.yaml --s3-bucket kalko-sam --profile kalko
sam deploy --template-file serverless-output.yaml --stack-name kalko-serverless-nodejs --capabilities CAPABILITY_IAM --profile kalko
