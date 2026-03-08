Then deploy with:
kustomize build . | kubectl apply -f -
# or
kubectl apply -k .
