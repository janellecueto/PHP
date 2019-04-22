<h2>Example calling a php script in an AJAX request</h2>
<section>
    Disclaimer: the included files do not work as a standalone! I've taken code from an intranet application I wrote for my office, therefore several items are missing such as the $host, $user, and $password needed to connect to the MySQL db.
</section>
<br>
<section>
  <ul>
    <li>index.html
      <ul><li>Simple HTML form with several text input fields and buttons for sending form information to other scripts</li></ul>
    </li>
    <li>style.css
      <ul><li>Basic CSS styling for index.html. nothing special</li></ul>
    </li>
    <li>Scripts/javascript.js
      <ul>
        <li>This is in pure javascript (no jQuery)</li>
        <li>Includes several functions called in the onchange and onclick events for form elements in index.html</li>
        <li>Functions use XMLHttpRequest() to call PHP scripts. </li>
      </ul>
    </li>
  </ul>
</section>
