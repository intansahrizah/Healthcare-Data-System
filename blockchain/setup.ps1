Write-Host "=== Setting Up Hyperledger Fabric ===" -ForegroundColor Green

# Create directory structure
$directories = @("bin", "config", "chaincode", "api", "fabric-samples")
foreach ($dir in $directories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir
        Write-Host "Created directory: $dir" -ForegroundColor Yellow
    }
}

# Download Fabric samples if missing
if (!(Test-Path "fabric-samples\test-network")) {
    Write-Host "Downloading Fabric samples..." -ForegroundColor Yellow
    $url = "https://github.com/hyperledger/fabric-samples/archive/main.zip"
    $output = "fabric-samples.zip"
    
    try {
        Invoke-WebRequest -Uri $url -OutFile $output
        Expand-Archive -Path $output -DestinationPath "fabric-samples-temp" -Force
        
        # Move contents to fabric-samples folder
        if (Test-Path "fabric-samples-temp\fabric-samples-main") {
            Move-Item -Path "fabric-samples-temp\fabric-samples-main\*" -Destination "fabric-samples\" -Force
        }
        Remove-Item -Path "fabric-samples-temp" -Recurse -Force -ErrorAction SilentlyContinue
        Remove-Item -Path $output -Force -ErrorAction SilentlyContinue
        Write-Host "Fabric samples downloaded successfully!" -ForegroundColor Green
    } catch {
        Write-Host "Error downloading Fabric samples: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Download Fabric binaries
Write-Host "Downloading Fabric binaries..." -ForegroundColor Yellow
$fabricUrl = "https://github.com/hyperledger/fabric/releases/download/v2.5.12/hyperledger-fabric-windows-amd64-2.5.12.tar.gz"
$binariesOutput = "fabric-binaries.tar.gz"

try {
    Invoke-WebRequest -Uri $fabricUrl -OutFile $binariesOutput
    
    # Try to extract using tar (if available)
    if (Get-Command tar -ErrorAction SilentlyContinue) {
        tar -xzf $binariesOutput -C .
        Write-Host "Binaries extracted successfully!" -ForegroundColor Green
    } else {
        Write-Host "Download completed. Please install Git Bash to extract the files." -ForegroundColor Yellow
        Write-Host "Or manually extract fabric-binaries.tar.gz using 7-Zip" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error downloading Fabric binaries: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nSetup completed! Checking created folders:" -ForegroundColor Green
Get-ChildItem -Directory

Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. If binaries weren't extracted, install Git Bash from https://git-scm.com/download/win" -ForegroundColor White
Write-Host "2. Then run: tar -xzf fabric-binaries.tar.gz" -ForegroundColor White
Write-Host "3. Continue with the network setup" -ForegroundColor White