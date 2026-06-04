<?php
/**
 * payments/index.php - إعادة توجيه إلى المركز المالي
 */
header("Location: ../reports/financial-hub.php?tab=payments" . ($_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : ''));
exit;
