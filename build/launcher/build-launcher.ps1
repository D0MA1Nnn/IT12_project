$ErrorActionPreference = 'Stop'

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$sourcePath = Join-Path $PSScriptRoot 'SenadorCocoLauncher.cs'
$logoPath = Join-Path $projectRoot 'public\images\senador-coco-logo.png'
$iconPath = Join-Path $PSScriptRoot 'senador-coco-logo.ico'
$outputPath = Join-Path $projectRoot 'Senador Coco.exe'

if (-not (Test-Path $logoPath)) {
    throw "Logo file was not found: $logoPath"
}

$pngBytes = [System.IO.File]::ReadAllBytes($logoPath)
$iconStream = New-Object System.IO.FileStream($iconPath, [System.IO.FileMode]::Create, [System.IO.FileAccess]::Write)
$writer = New-Object System.IO.BinaryWriter($iconStream)

try {
    $writer.Write([UInt16]0)
    $writer.Write([UInt16]1)
    $writer.Write([UInt16]1)
    $writer.Write([Byte]0)
    $writer.Write([Byte]0)
    $writer.Write([Byte]0)
    $writer.Write([Byte]0)
    $writer.Write([UInt16]1)
    $writer.Write([UInt16]32)
    $writer.Write([UInt32]$pngBytes.Length)
    $writer.Write([UInt32]22)
    $writer.Write($pngBytes)
}
finally {
    $writer.Close()
    $iconStream.Close()
}

[void][System.Reflection.Assembly]::LoadWithPartialName('Microsoft.CSharp')

$provider = New-Object Microsoft.CSharp.CSharpCodeProvider
$parameters = New-Object System.CodeDom.Compiler.CompilerParameters
$parameters.GenerateExecutable = $true
$parameters.GenerateInMemory = $false
$parameters.OutputAssembly = $outputPath
$parameters.CompilerOptions = "/target:winexe /win32icon:`"$iconPath`""
[void]$parameters.ReferencedAssemblies.Add('System.dll')
[void]$parameters.ReferencedAssemblies.Add('System.Windows.Forms.dll')
[void]$parameters.ReferencedAssemblies.Add('System.Drawing.dll')

$result = $provider.CompileAssemblyFromFile($parameters, $sourcePath)

if ($result.Errors.HasErrors) {
    $messages = $result.Errors | ForEach-Object { $_.ToString() }
    throw ($messages -join [Environment]::NewLine)
}

Write-Host "Created launcher: $outputPath"
