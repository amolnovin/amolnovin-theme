<?php

// Theme template wrapper: every request is rendered by the Blade view Acorn selected (see resources/views).
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blade escapes all dynamic values while rendering.
echo view(app('sage.view'), app('sage.data'))->render();
