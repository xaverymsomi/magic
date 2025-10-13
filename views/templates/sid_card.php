<html lang="en_US">
<head>
    <title>IDENTITY CARD</title>
</head>

<style>
    /*! CSS Used from: Embedded */
    #printDiv{
        height: 201.8px;
        /* width:86mm; */
        width: 100mm;
    }
    body {
        height: 50mm;
        display: block;
        font-family: 'Source Sans Pro', "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #000000 !important;
        font-weight: 300;
        margin:0;
        padding:0;

    }
    /* .vaccination-number{
        padding-top: 5px;
        height: 105px;
    } */

</style>

<body>
<div id="printDiv">
    <div class="card-content" style="width: 100%;">
        <div class="content" style="width: 65%; float: right;">
            <div class="col-md-6 col-lg-6">
                <div class="vaccination-number">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Surname <br><span style="color: black; opacity: 90%; text-align: start;                                              font-size: 30px;">
                                    {{ form.txt_last_name }}</span>
                    </h5>
                </div>
                <div class="card-holder">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Given Name<br>
                        <span style="color: black; opacity: 90%; text-align: start;  font-size: 30px;">
                                        {{ form.txt_first_name + ' ' +form.txt_middle_name }} </span>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px; ">
                        Date Of Issue<br>
                        <div ng-if="form['Issued Date'] == null">
                            <h3>Not Issued</h3>
                        </div>
                        <div ng-if="form['Issued Date'] != null">
                            <h3>{{form['Issued Date']}}</h3>
                        </div>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Date Of Birth<br>
                        <span style="color: black; opacity: 90%; text-align:center; font-size: 30px;">
                                        {{ form['Date of Birth']}}</span>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Place Of Birth<br>
                        <span style="color: black; opacity: 90%; text-align:center; font-size: 30px;">
                                        {{ form['Place of Birth']}}</span>
                    </h5>
                </div>
            </div>
            <div class="col-md-6 col-lg-6">
                <div class="vaccination-number">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        ID No <br><span style="color: black; opacity: 90%; text-align: start; font-size:30px;">
                                    {{ form['SID Number'] }}</span>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Date Of Expiry<br>
                        <div ng-if="form['Expiry Date'] == null">
                            <h3>Not Issued</h3>
                        </div>
                        <div ng-if="form['Expiry Date'] != null">
                            <h3>{{form['Expiry Date']}}</h3>
                        </div>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Nationality<br>
                        <span style="color: black; opacity: 90%; text-align:center; font-size: 30px; ">
                                        {{ form['Nationality']}}</span>
                    </h5>
                </div>
                <div class="vaccine-name">
                    <h5 style="color: black; opacity: 90%; text-align: start; font-size: 18px;">
                        Gender<br>
                        <span style="color: black; opacity: 90%; text-align:center; font-size: 30px; ">
                                        {{ form['Gender']}}</span>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>
<br>
</body>

</html>
