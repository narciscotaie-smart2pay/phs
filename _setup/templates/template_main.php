<?php
/** @var \phs\setup\libraries\PHS_Setup_view $this */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport"           content="user-scalable=no, width=device-width, initial-scale=1.0" />
    <meta name="copyright"          content="Copyright <?php echo date( 'Y' )?> PHS. All Right Reserved." />
    <meta name="author"             content="PHS Framework" />
    <meta name="revisit-after"      content="1 days" />

    <title><?php echo (($page_title = $this->get_context( 'page_title' ))?$page_title.' - ':'')?>PHS Setup</title>
</head>
<style>
html,body { margin:0; padding:0; height:100%; min-width: 650px; border: 0; outline: 0; }
html { background: #f2f3f4; font-size: 62.5%; -webkit-overflow-scrolling: touch; -webkit-tap-highlight-color: #f3f5f6; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
body { font-family: 'Open Sans', 'Segoe UI', Arial, Helvetica, 'Trebuchet MS', sans-serif; font-size: 14px; line-height: 1.5em; background: #f2f3f4; color: #444; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%;  margin: 0; padding: 0; height:100%; }
#container { min-height:100%; max-width: 100%; position:relative; background: #F0F0F0  url('data:image/gif;base64,R0lGODlhBQAFAIAAAOLi4vDw8CH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpBMDNCRTNFOTg3QTYxMUU2ODkxNEUxRURBMDA3MzE2RCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpBMDNCRTNFQTg3QTYxMUU2ODkxNEUxRURBMDA3MzE2RCI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkEwM0JFM0U3ODdBNjExRTY4OTE0RTFFREEwMDczMTZEIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkEwM0JFM0U4ODdBNjExRTY4OTE0RTFFREEwMDczMTZEIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+Af/+/fz7+vn49/b19PPy8fDv7u3s6+rp6Ofm5eTj4uHg397d3Nva2djX1tXU09LR0M/OzczLysnIx8bFxMPCwcC/vr28u7q5uLe2tbSzsrGwr66trKuqqainpqWko6KhoJ+enZybmpmYl5aVlJOSkZCPjo2Mi4qJiIeGhYSDgoGAf359fHt6eXh3dnV0c3JxcG9ubWxramloZ2ZlZGNiYWBfXl1cW1pZWFdWVVRTUlFQT05NTEtKSUhHRkVEQ0JBQD8+PTw7Ojk4NzY1NDMyMTAvLi0sKyopKCcmJSQjIiEgHx4dHBsaGRgXFhUUExIREA8ODQwLCgkIBwYFBAMCAQAAIfkEAAAAAAAsAAAAAAUABQAAAgWMjwbJUQA7'); }
#content { padding: 50px; }
#main_content { max-width: 68.5em; margin: 0 auto; }
.lineform_line { margin: 5px 0 5px 15em; }
fieldset.form-group label { float: left; margin-top: 5px; color: #555; font-family: "Segoe UI", Helvetica, Arial, Verdana, sans-serif; }
label { display: inline-block; max-width: 100%; margin-bottom: 5px; font-weight: 700; }
form fieldset { max-width: 100%; border: 0; line-height: 24px; vertical-align: middle; }
form fieldset input { border: 2px solid grey; padding: 5px; border-radius: 3px; }
fieldset { padding: .35em .625em .75em; margin: 0 2px; border: 1px solid silver; }
</style>
<body>
<div id="container">
    <div id="content">
        <div id="main_content">

            <?php echo (($page_content = $this->get_context( 'page_content' ))?$page_content:'')?>

        </div>
    </div>
</div>
</body>
</html>
