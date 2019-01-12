#!/bin/bash
export AWS_IP=ec2-user@52.58.157.7
export SSH_KEY_FILE=~/magistum-pwa/files/appmagistumcom.pem
scp -i $SSH_KEY_FILE ~/aws-lambda-layer-php73/docs/compile_php.sh $AWS_IP:compile_php.sh
ssh -i $SSH_KEY_FILE -t $AWS_IP "chmod a+x compile_php.sh && ./compile_php.sh 7.3.1"
scp -i $SSH_KEY_FILE $AWS_IP:/home/ec2-user/php-7-bin/bin/php ~/aws-lambda-layer-php73/layer/php