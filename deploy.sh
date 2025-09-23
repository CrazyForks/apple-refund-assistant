#!/usr/bin/env sh

#!/usr/bin/env sh

echo "build image"
docker build -t apple-refund-assistant-image .

docker run -d -p 8080:8080 --name apple-refund-assistant apple-refund-assistant-image
docker exec apple-refund-assistant php artisan optimize:clear
docker exec apple-refund-assistan php artisan optimize
docker exec apple-refund-assistant php artisan app:init
