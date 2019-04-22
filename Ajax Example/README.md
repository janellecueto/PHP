<h2>Example calling a php script in an AJAX request</h2>
<section>
    Disclaimer: the included files do not work as a standalone! I've taken code from an intranet application I wrote for my office, therefore several items are missing such as the $host, $user, and $password needed to connect to the MySQL db.
</section>
<br><br>
<section>
    <ul>
        <li><b>index.html</b>
            <ul><li>Simple HTML form with several text input fields and buttons for sending form information to other scripts</li></ul>
        </li>
        <li><b>style.css</b>
            <ul><li>Basic CSS styling for index.html. nothing special</li></ul>
        </li>
        <li><b>Scripts/javascript.js</b>
            <ul>
                <li>This is in pure javascript (no jQuery)</li>
                <li>Includes several functions called in the onchange and onclick events for form elements in index.html</li>
                <li>Functions use XMLHttpRequest() to call PHP scripts. </li>
                <li>Since this repository is about PHP, I won't be explaining javascript topics</li>
            </ul>
        </li>
        <li><b>Scripts/fillInfo.php</b>
            <ul>
                <li>Queries MySQL table for project and client information and fills other form fields if the project or client exist</li>
            </ul>
        </li>
        <li><b>Scripts/checkSignature.php</b>
            <ul>
                <li>Given someone's initials and their company's client code, queries for their full name to be displayed in the form</li>
            </ul>
        <li>
        <li><b>Scripts/sendToEnvelopePrinter</b>
            <ul><li>Writes PCL directly to a printer on the server to print out an envelope</li></ul>
        </li>
  </ul>
</section>
