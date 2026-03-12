export SOFTWAREVERSION=0.0.2
docker build . \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:latest \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:release \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:${SOFTWAREVERSION}
echo "#  Pushing Container #"
docker push telur.penyahpepijat.com/enigma/quivitech/inventory-management -a
docker image rm telur.penyahpepijat.com/enigma/quivitech/inventory-management:latest \
    telur.penyahpepijat.com/enigma/quivitech/inventory-management:release \
    telur.penyahpepijat.com/enigma/quivitech/inventory-management:${SOFTWAREVERSION}
