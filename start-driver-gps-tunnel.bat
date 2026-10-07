@echo off
title REGC Mobile Driver GPS Gateway (Cellular Data)
cls
echo ====================================================================
echo   REGC Trucking Services - Remote Mobile Driver GPS Gateway
echo ====================================================================
echo.
echo Starting secure Cloudflare tunnel for drivers on mobile cellular data...
echo (Allows drivers to broadcast live GPS using their phone load/data)
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\start-tunnel.ps1"

pause

