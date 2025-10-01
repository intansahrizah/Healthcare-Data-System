
#!/bin/bash
echo "=== Fabric Network Diagnostic ==="
cd /c/laragon/www/HealthcareSystem/blockchain
echo "1. Checking directory structure..."
ls -la
echo "2. Looking for bin directories..."
find . -name "bin" -type d 2>/dev/null
echo "3. Checking fabric-samples structure..."
ls -la fabric-samples/
echo "4. Checking if fabric-samples/bin exists..."
if [ -d "fabric-samples/bin" ]; then
    echo "fabric-samples/bin exists"
    ls -la fabric-samples/bin/
else
    echo "fabric-samples/bin does not exist"
fi
echo "5. Checking PATH..."
echo $PATH | tr ':' '\n' | grep -i fabric
echo "6. Checking for peer command..."
which peer || echo "peer not found in PATH"
type peer || echo "peer not found"
echo "7. Trying direct execution..."
if [ -f "fabric-samples/bin/peer" ]; then
    ./fabric-samples/bin/peer version || echo "Direct execution failed"
elif [ -f "fabric-samples/bin/peer.exe" ]; then
    ./fabric-samples/bin/peer.exe version || echo "Direct execution failed"
else
    echo "No peer executable found"
fi
