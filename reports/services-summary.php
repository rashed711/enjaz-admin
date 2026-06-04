<?php
/**
 * reports/services-summary.php - إعادة توجيه إلى المركز المالي
 */
header("Location: financial-hub.php?tab=services" . ($_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : ''));
exit;
