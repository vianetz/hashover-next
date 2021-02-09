FROM webdevops/php-nginx-dev:7.4-alpine

# Install mhsendmail for Mailhog
ENV GOPATH /tmp

RUN apk --no-cache add go && go get github.com/mailhog/mhsendmail && ln -s /tmp/bin/mhsendmail /usr/local/bin/