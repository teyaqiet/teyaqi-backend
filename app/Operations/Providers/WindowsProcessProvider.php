<?php

namespace App\Operations\Providers;

use RuntimeException;
use Symfony\Component\Process\Process;

class WindowsProcessProvider implements ProcessProviderInterface
{
    /**
     * Get all running Windows processes.
     */
    public function list(): array
    {
        $script = <<<'PS'
$processorCount = [Environment]::ProcessorCount

if ($processorCount -lt 1) {
    $processorCount = 1
}

$sampleSeconds = 0.5

$firstCpu = @{}

# ------------------------------------------------------------
# First CPU snapshot
# ------------------------------------------------------------

Get-Process | ForEach-Object {

    try {

        $processId = [int]$_.Id
        $cpu = $_.CPU

        if ($null -ne $cpu) {
            $firstCpu[$processId] = [double]$cpu
        }

    } catch {
        # Some protected processes do not expose CPU information.
    }
}

# ------------------------------------------------------------
# CPU sampling interval
# ------------------------------------------------------------

Start-Sleep -Milliseconds 500

# ------------------------------------------------------------
# Second snapshot
# ------------------------------------------------------------

$processes = Get-Process

# ------------------------------------------------------------
# Build result
# ------------------------------------------------------------

$result = foreach ($p in $processes) {

    try {

        $processId = [int]$p.Id

        # ----------------------------------------------------
        # CPU
        # ----------------------------------------------------

        $cpuPercent = $null

        try {

            $newCpu = [double]$p.CPU

            if ($firstCpu.ContainsKey($processId)) {

                $oldCpu = $firstCpu[$processId]

                $delta = $newCpu - $oldCpu

                if ($delta -lt 0) {
                    $delta = 0
                }

                $cpuPercent = (
                    $delta /
                    $sampleSeconds /
                    $processorCount
                ) * 100

                $cpuPercent = [math]::Round(
                    $cpuPercent,
                    1
                )

                if ($cpuPercent -lt 0) {
                    $cpuPercent = 0
                }

                if ($cpuPercent -gt 100) {
                    $cpuPercent = 100
                }
            }

        } catch {
            $cpuPercent = $null
        }

        # ----------------------------------------------------
        # Memory
        # ----------------------------------------------------

        $memoryBytes = 0

        try {
            $memoryBytes = [int64]$p.WorkingSet64
        } catch {
            $memoryBytes = 0
        }

        # ----------------------------------------------------
        # Name
        # ----------------------------------------------------

        $name = ""

        try {
            $name = [string]$p.ProcessName
        } catch {
        }

        # ----------------------------------------------------
        # Start time
        # ----------------------------------------------------

        $startTime = $null

        try {
            $startTime = $p.StartTime.ToString(
                "yyyy-MM-dd HH:mm:ss"
            )
        } catch {
        }

        # ----------------------------------------------------
        # Process object
        # ----------------------------------------------------

        [PSCustomObject]@{
            pid            = $processId
            name           = $name
            cpu_percent    = $cpuPercent
            memory_bytes   = $memoryBytes
            memory_percent = $null
            user           = $null
            status         = "running"
            started_at     = $startTime
            command        = $null
        }

    } catch {
        # Ignore processes that disappear while being inspected.
    }
}

# ------------------------------------------------------------
# Sort by CPU
# ------------------------------------------------------------

$result = @(
    $result |
        Sort-Object cpu_percent -Descending
)

# ------------------------------------------------------------
# Return JSON
# ------------------------------------------------------------

if ($result.Count -eq 0) {

    Write-Output "[]"

} else {

    $result |
        ConvertTo-Json -Compress
}
PS;

        return $this->runPowerShell($script);
    }

    /**
     * Find a single process by PID.
     */
    public function find(int $processId): ?array
    {
        $script = sprintf(
            <<<'PS'
$targetProcessId = %d

$process = Get-Process `
    -Id $targetProcessId `
    -ErrorAction SilentlyContinue

if (-not $process) {

    Write-Output "null"

    exit 0
}

$memoryBytes = 0

try {
    $memoryBytes = [int64]$process.WorkingSet64
} catch {
}

$cpu = $null

try {
    $cpu = [double]$process.CPU
} catch {
}

$startTime = $null

try {

    $startTime = $process.StartTime.ToString(
        "yyyy-MM-dd HH:mm:ss"
    )

} catch {
}

[PSCustomObject]@{
    pid            = [int]$process.Id
    name           = [string]$process.ProcessName
    cpu_percent    = $cpu
    memory_bytes   = $memoryBytes
    memory_percent = $null
    user           = $null
    status         = "running"
    started_at     = $startTime
    command        = $null
} |
    ConvertTo-Json -Compress
PS,
            $processId
        );

        return $this->runPowerShellRaw($script);
    }

    /**
     * Provider information.
     */
    public function info(): array
    {
        return [
            'platform' => 'windows',
            'provider' => static::class,
        ];
    }

    /**
     * Normalize PowerShell process output into an array.
     */
    protected function runPowerShell(string $script): array
    {
        $result = $this->runPowerShellRaw($script);

        if ($result === null || $result === []) {
            return [];
        }

        // PowerShell returns a single object when only one
        // process exists.
        if (
            isset($result['pid']) &&
            !isset($result[0])
        ) {
            return [$result];
        }

        return array_values(
            array_filter(
                $result,
                fn ($process) => is_array($process)
            )
        );
    }

    /**
     * Execute PowerShell using UTF-16LE EncodedCommand.
     */
    protected function runPowerShellRaw(string $script): mixed
    {
        $encodedCommand = base64_encode(
            mb_convert_encoding(
                $script,
                'UTF-16LE',
                'UTF-8'
            )
        );

        $process = new Process([
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-EncodedCommand',
            $encodedCommand,
        ]);

        $process->setTimeout(60);

        $process->run();

        if (!$process->isSuccessful()) {

            throw new RuntimeException(
                "PowerShell process failed.\n\n" .
                "Exit code: " .
                $process->getExitCode() .
                "\n\n" .
                "ERROR OUTPUT:\n" .
                $process->getErrorOutput() .
                "\n\n" .
                "STANDARD OUTPUT:\n" .
                $process->getOutput()
            );
        }

        $output = trim(
            $process->getOutput()
        );

        if ($output === '') {

            throw new RuntimeException(
                "PowerShell returned empty output."
            );
        }

        $decoded = json_decode(
            $output,
            true
        );

        if (
            json_last_error() !== JSON_ERROR_NONE
        ) {

            throw new RuntimeException(
                "PowerShell returned invalid JSON.\n\n" .
                "JSON error: " .
                json_last_error_msg() .
                "\n\n" .
                "RAW OUTPUT:\n" .
                $output
            );
        }

        if ($decoded === null) {
            return null;
        }

        if (!is_array($decoded)) {

            throw new RuntimeException(
                "PowerShell JSON result is not an array or object."
            );
        }

        return $decoded;
    }
}