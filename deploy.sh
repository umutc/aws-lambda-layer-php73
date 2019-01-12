#!/bin/bash

sam package --template-file template.yaml --output-template-file serverless-output.yaml --s3-bucket magistum-sam
sam deploy --template-file serverless-output.yaml --stack-name magistum-serverless-php73 --capabilities CAPABILITY_IAM