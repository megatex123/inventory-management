#export SOFTWAREVERSION=0.0.3
#source .env
composer install && npm install && npm run dev
. ./.env
echo "**********************************"
echo "* Version is " $SOFTWAREVERSION " *"
echo "**********************************"
docker build . \
    -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:latest \
    -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:release \
    -t telur.enigmacode.com.my/enigma/quivitech/inventory-management:${SOFTWAREVERSION}
echo "#  Pushing Container #"
docker push telur.enigmacode.com.my/enigma/quivitech/inventory-management -a
docker image rm telur.enigmacode.com.my/enigma/quivitech/inventory-management:latest \
    telur.enigmacode.com.my/enigma/quivitech/inventory-management:release \
    telur.enigmacode.com.my/enigma/quivitech/inventory-management:${SOFTWAREVERSION}
