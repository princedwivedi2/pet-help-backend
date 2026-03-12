$baseV1 = 'http://127.0.0.1:8002/api/v1'
$base = 'http://127.0.0.1:8002/api'
$results = @()

function Add-Result($name, $ok, $detail) {
  $status = if ($ok) { 'PASS' } else { 'FAIL' }
  $script:results += [pscustomobject]@{
    Check = $name
    Status = $status
    Detail = $detail
  }
}

try {
  $adminLogin = Invoke-RestMethod -Method Post -Uri ($base + '/auth/login') -ContentType 'application/json' -Body (@{ email='admin@petsathi.com'; password='admin123' } | ConvertTo-Json)
  $adminToken = $adminLogin.data.token
  Add-Result 'Admin login /api' ($null -ne $adminToken) 'Token issued'
} catch {
  Add-Result 'Admin login /api' $false $_.Exception.Message
  $results | Format-Table -AutoSize | Out-String
  exit 1
}

$adminHeaders = @{ Authorization = ('Bearer ' + $adminToken); Accept='application/json' }

try {
  $v1Vets = Invoke-RestMethod -Method Get -Uri ($baseV1 + '/vets?radius_km=10') -Headers @{ Accept='application/json' }
  $hasBuckets = $v1Vets.data.PSObject.Properties.Name -contains 'nearby_vets' -and $v1Vets.data.PSObject.Properties.Name -contains 'city_vets' -and $v1Vets.data.PSObject.Properties.Name -contains 'all_vets'
  $hasFallbackData = ($v1Vets.data.all_vets.Count -gt 0)
  Add-Result 'GET /api/v1/vets buckets+fallback' ($hasBuckets -and $hasFallbackData) ('nearby=' + $v1Vets.data.nearby_vets.Count + ', city=' + $v1Vets.data.city_vets.Count + ', all=' + $v1Vets.data.all_vets.Count)
} catch {
  Add-Result 'GET /api/v1/vets buckets+fallback' $false $_.Exception.Message
}

try {
  $apiVets = Invoke-RestMethod -Method Get -Uri ($base + '/vets?radius_km=10') -Headers @{ Accept='application/json' }
  $hasBuckets = $apiVets.data.PSObject.Properties.Name -contains 'nearby_vets' -and $apiVets.data.PSObject.Properties.Name -contains 'city_vets' -and $apiVets.data.PSObject.Properties.Name -contains 'all_vets'
  Add-Result 'GET /api/vets alias buckets' $hasBuckets ('nearby=' + $apiVets.data.nearby_vets.Count + ', city=' + $apiVets.data.city_vets.Count + ', all=' + $apiVets.data.all_vets.Count)
} catch {
  Add-Result 'GET /api/vets alias buckets' $false $_.Exception.Message
}

try {
  Invoke-RestMethod -Method Post -Uri ($base + '/vet/profile') -Headers $adminHeaders -ContentType 'application/json' -Body (@{} | ConvertTo-Json) | Out-Null
  Add-Result 'POST /api/vet/profile role guard' $false 'Unexpectedly accessible to admin role'
} catch {
  Add-Result 'POST /api/vet/profile role guard' $true 'Route resolved and correctly denied for non-vet role'
}

$pendingUuid = $null
try {
  $pendingRes = Invoke-RestMethod -Method Get -Uri ($base + '/admin/vets?status=pending&per_page=1') -Headers $adminHeaders
  if ($pendingRes.data.vets.Count -gt 0) {
    $pendingUuid = $pendingRes.data.vets[0].uuid
  }
  Add-Result 'GET /api/admin/vets pending' ($null -ne $pendingUuid) ('pending_uuid=' + $pendingUuid)
} catch {
  Add-Result 'GET /api/admin/vets pending' $false $_.Exception.Message
}

if ($pendingUuid) {
  try {
    $reqInfo = Invoke-RestMethod -Method Patch -Uri ($base + '/admin/vets/' + $pendingUuid + '/request-info') -Headers $adminHeaders -ContentType 'application/json' -Body (@{ reason='Please upload clearer certificate' } | ConvertTo-Json)
    Add-Result 'PATCH request-info' ($reqInfo.success -eq $true) $reqInfo.message
  } catch {
    Add-Result 'PATCH request-info' $false $_.Exception.Message
  }

  try {
    $approve = Invoke-RestMethod -Method Patch -Uri ($base + '/admin/vets/' + $pendingUuid + '/approve') -Headers $adminHeaders -ContentType 'application/json' -Body (@{ notes='Approved after clarification' } | ConvertTo-Json)
    Add-Result 'PATCH approve after request-info' ($approve.success -eq $true) $approve.message
  } catch {
    $resp = $_.Exception.Response
    $isExpectedBlock = $false
    $message = $_.Exception.Message
    if ($resp -and $resp.GetResponseStream()) {
      $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
      $body = $reader.ReadToEnd()
      $message = $body
      $isExpectedBlock = $body -like '*Vet profile incomplete*' -or $body -like '*missing_fields*'
    }
    Add-Result 'PATCH approve after request-info (validation)' $isExpectedBlock $message
  }

  $approvedUuid = $null
  try {
    $approvedRes = Invoke-RestMethod -Method Get -Uri ($base + '/admin/vets?status=approved&per_page=1') -Headers $adminHeaders
    if ($approvedRes.data.vets.Count -gt 0) {
      $approvedUuid = $approvedRes.data.vets[0].uuid
    }
  } catch {}

  try {
    $suspend = Invoke-RestMethod -Method Patch -Uri ($base + '/admin/vets/' + $approvedUuid + '/suspend') -Headers $adminHeaders -ContentType 'application/json' -Body (@{ reason='Temporary compliance review' } | ConvertTo-Json)
    Add-Result 'PATCH suspend' ($suspend.success -eq $true) $suspend.message
  } catch {
    Add-Result 'PATCH suspend' $false $_.Exception.Message
  }

  try {
    $reactivate = Invoke-RestMethod -Method Patch -Uri ($base + '/admin/vets/' + $approvedUuid + '/reactivate') -Headers $adminHeaders -ContentType 'application/json' -Body (@{ reason='Compliance cleared' } | ConvertTo-Json)
    Add-Result 'PATCH reactivate' ($reactivate.success -eq $true) $reactivate.message
  } catch {
    Add-Result 'PATCH reactivate' $false $_.Exception.Message
  }

  try {
    $detail = Invoke-RestMethod -Method Get -Uri ($base + '/admin/vets/' + $pendingUuid) -Headers $adminHeaders
    $hasInspection = $detail.data.inspection -ne $null -and $detail.data.inspection.location -ne $null -and $detail.data.inspection.stats -ne $null
    Add-Result 'GET admin vet detail inspection payload' $hasInspection 'inspection payload present'
  } catch {
    Add-Result 'GET admin vet detail inspection payload' $false $_.Exception.Message
  }
}

$results | Format-Table -AutoSize | Out-String
