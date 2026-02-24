import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { ComboBoxComponent } from '@syncfusion/ej2-angular-dropdowns';
import { formatNumber } from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';
import { MyToastService } from '../../../services/toaster.server';

@Component({
  selector: 'app-credit-list',
  templateUrl: './credit-list.component.html',
  styleUrls: ['./credit-list.component.scss'],
})
export class CreditlistComponent implements OnInit {
  @ViewChild('RptTable') RptTable: any;
  @ViewChild('cmbRoute') cmbRoute! : ComboBoxComponent;

  public Filter = {
    City: '',
    Balance: '10',
    Cnic: ''
  };
  setting = {
    Checkbox: false,
    Columns: [
      {
        label: 'C. Code',
        fldName: 'CustomerID',
      },
      {
        label: 'Customer Name',
        fldName: 'CustomerName',
      },
      {
        label: 'Address',
        fldName: 'Address',
      },
      {
        label: 'City',
        fldName: 'City',
      },
      {
        label: 'Cnic',
        fldName: 'Cnic',
      },
      {
        label: 'Phone No',
        fldName: 'PhoneNo1',
      },
      {
        label: 'Amount',
        fldName: 'Balance',
        sum: true,
        valueFormatter: (d: any) => {
          return formatNumber(d['Balance']);
        },
      },

    ],
    Actions: [

    ],
    Data: [],
  };

  public Cities:any=[];
  public data: object[] = [];

  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private cachedData: CachedDataService,
    private myToaster: MyToastService,
    private router: Router
  ) {
    this.Cities = this.cachedData.routes$;
  }

  ngOnInit() {
    this.http.getData('qrycities?orderby=City').then((a) => {
      this.Cities = a;
    });
    this.FilterData();
  }

  FilterData() {
    let filter =
      "1=1";
    if (this.Filter.City) filter += ` and City='${this.Filter.City}'`
    if (this.Filter.Balance) filter += ' and Balance >=' + this.Filter.Balance

    // request the actual view columns and alias them to the field names used by the table
    this.http.getData('qrycustomers?flds=CustomerID,CustomerName, Address, City, CNICNo as Cnic, PhoneNo as PhoneNo1, Balance as Balance&filter=' + filter).then((r: any) => {
      // normalize incoming data to ensure `Cnic` and `PhoneNo1` are populated
      if (Array.isArray(r)) {
        r = r.map((it: any) => {
          // try several possible property names that may come from different DB views
          it.Cnic = it.Cnic || it.CNICNo || it.CNIC || it.CnicNo || it.CNIC_No || '';
          it.PhoneNo1 = it.PhoneNo1 || it.PhoneNo || it.Phone || it.PhoneNo_1 || '';
          // trim whitespace
          if (typeof it.Cnic === 'string') it.Cnic = it.Cnic.trim();
          if (typeof it.PhoneNo1 === 'string') it.PhoneNo1 = it.PhoneNo1.trim();
          return it;
        });
      }
      this.data = r;
    });
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title = 'Credit List';
    this.ps.PrintData.SubTitle =
      'City: ' + this.cmbRoute.text;

    this.router.navigateByUrl('/print/print-html');
  }
}
