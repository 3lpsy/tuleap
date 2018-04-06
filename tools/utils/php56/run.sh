#!/usr/bin/env bash

set -ex

/opt/rh/rh-php56/root/usr/bin/php /usr/share/tuleap/tools/utils/php56/run.php --development

while [ ! -f /etc/pki/ca-trust/source/anchors/tuleap-realtime-cert.pem ]; do
    echo "Waiting for Tuleap Realtime certificate…"
    sleep 1
done

echo "Tuleap Realtime certificate has been found. Adding to the CA bundle."
update-ca-trust enable
update-ca-trust extract

replacement=`echo $REALTIME_KEY | sed "s|/|\\\\\/|g"`
sed -e "s/\$nodejs_server_jwt_private_key = '';/\$nodejs_server_jwt_private_key = '$replacement';/" \
    -e "s/\$nodejs_server = '';/\$nodejs_server = 'tuleap-web.tuleap-aio-dev.docker:443';/" \
    -e "s/\$nodejs_server_int = '';/\$nodejs_server_int = 'realtime';/" \
    -i /etc/tuleap/conf/local.inc
