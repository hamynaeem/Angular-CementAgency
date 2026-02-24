import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
import { PrintDataService } from '../../../services/print.data.services';

@Component({
  selector: 'app-expense-report',
  templateUrl: './expense-report.component.html',
  styleUrls: ['./expense-report.component.scss']
})
export class ExpenseReportComponent implements OnInit {
  public data: object[] = [];
  public Heads: object[] = [];

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    HeadID: '',

  };
  setting = {
    Columns: [
      {
        label: 'Date',
        fldName: 'Date'
      },
      {
        label: 'Head',
        fldName: 'HeadName'
      },
      {
        label: 'Description',
        fldName: 'Description'
      },

      {
        label: 'Amount',
        fldName: 'Amount',
        sum: true
      },

    ],
    Actions: [
    ],
    Data: []
  };


  public toolbarOptions: object[] = [];
  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private router: Router
  ) { }

  ngOnInit() {
    this.http.getData('expensehead').then((r: any) => {
      // ensure dropdown text field exists — API returns `Head` but template expects `HeadName`
      this.Heads = (r || []).map((h: any) => ({ ...h, HeadName: h.Head || h.HeadName }));
    });

    this.FilterData();

  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title = 'Expense Report';
    this.ps.PrintData.SubTitle = 'From :' + JSON2Date(this.Filter.FromDate) + ' To: ' + JSON2Date(this.Filter.ToDate);

    this.router.navigateByUrl('/print/print-html');
  }
  FilterData() {
    // qualify columns with table alias to avoid ambiguous column errors
    // tslint:disable-next-line:quotemark
    let filter = `e.Date between '${JSON2Date(this.Filter.FromDate)}' and '${JSON2Date(this.Filter.ToDate)}'`;


    if (!(this.Filter.HeadID === '' || this.Filter.HeadID === null)) {
      filter += ' and e.HeadID=' + this.Filter.HeadID;
    }
    this.http.getData('qryexpenses?filter=' + filter).then((r: any) => {
      this.data = r;
    });
  }
  Clicked(e: any) {
    console.log(e);

  }
}
