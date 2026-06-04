<?php
/**
 * invoices/index.php - إعادة توجيه إلى المركز المالي
 */
header("Location: ../reports/financial-hub.php?tab=invoices" . ($_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : ''));
exit;
