docker build . \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:latest \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:0.0.1 \
    -t telur.penyahpepijat.com/enigma/quivitech/inventory-management:release
echo "#  Pushing Container #"
docker push telur.penyahpepijat.com/enigma/quivitech/inventory-management -a
