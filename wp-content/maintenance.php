<?php
  if (defined('ABSPATH')) {
    if (file_exists(ABSPATH . '/.maintenance') || wp_installing()) {
      @unlink(ABSPATH . '/.maintenance');
    }
  }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Redirecting...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style media="screen">
      html, body {
        padding: 0;
        margin: 0;
      }

      #preloader {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: #ECE9E6;
        background: -webkit-linear-gradient(to bottom, #fff, #ECE9E6);
        background: linear-gradient(to bottom, #fff, #ECE9E6);
        min-height: 100vh;
        min-width: 100vw;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
      }

      sup {
        font-size: 19px;
        color: #666;
      }

      @-webkit-keyframes breathing {
        0% {
          -webkit-transform: scale(0.9);
          transform: scale(0.9);
        }

        50% {
          -webkit-transform: scale(1);
          transform: scale(1);
        }

        100% {
          -webkit-transform: scale(0.9);
          transform: scale(0.9);
        }
      }

      @keyframes breathing {
        0% {
          -webkit-transform: scale(0.9);
          -ms-transform: scale(0.9);
          transform: scale(0.9);
        }

        50% {
          -webkit-transform: scale(1);
          -ms-transform: scale(1);
          transform: scale(1);
        }

        100% {
          -webkit-transform: scale(0.9);
          -ms-transform: scale(0.9);
          transform: scale(0.9);
        }
      }

      #center {
        -webkit-animation: breathing 1.5s ease-out infinite normal;
        animation: breathing 1.5s ease-out infinite normal;
        -webkit-font-smoothing: antialiased;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <input type="text" hidden value="wp_site_maintenance_mode_on">
    <div id="preloader">
      <div id="center">
        <div>
          <sup><b>LOADING</b></sup>
        </div>
        <img src="https://tastewp.com/assets/svgs/tlogo.svg" height="120px" alt="T Letter">
      </div>
    </div>

    <script type="text/javascript">

      function getAppendQuery() {

        let query = window.location.search;
        if (query && query[0] == '?') query = query.slice(1);
        query = query.split('&');

        let parsed = {};
        for (let key in query) {
          let param = query[key];
          if (!param.includes('=')) continue;
          else {
            param = param.split('=');
            parsed[param[0]] = param[1];
          }
        }

        let output = '';
        if (typeof parsed['redirect-menu'] != 'undefined') {

          return '&redirect-menu=' + encodeURIComponent(parsed['redirect-menu']);

        } else if (typeof parsed['redirect'] != 'undefined') {

          return '&redirect=' + parsed['redirect'];

        } else {

          return '';

        }

      }

      function tryToRedirect() {
        setTimeout(() => {
          fetch('/active.html').then((e) => {
            if (e.status === 200) {
              setTimeout(() => {
                window.location = '/wp-login.php?autologin=true' + getAppendQuery();
              }, 1000);
            } else tryToRedirect();
          }).catch((error) => {
            tryToRedirect();
          });
        }, 600);
      }
      tryToRedirect();

      setTimeout(() => {
        window.location = '/wp-login.php?autologin=true' + getAppendQuery();
      }, 8000);
    </script>
  </body>
</html>
