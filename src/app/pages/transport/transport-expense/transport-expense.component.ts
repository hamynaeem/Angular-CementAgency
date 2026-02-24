import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { getCurDate, GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
import { TransportDetail } from '../transport.model';

@Component({
  selector: 'app-transport-expense',
  templateUrl: './transport-expense.component.html',
  styleUrls: ['./transport-expense.component.scss'],
})
export class TransportExpenseComponent implements OnInit {
  @ViewChild('cmbCustomer') cmbCustomer;
  public Voucher: TransportDetail;
  AcctTypes = [];
  EditID = '';
  public Ino = '';
  private AcctTypeID = '';
  curVehicle: any = {};
  Products: any = [];
  Vehicles: any = [];
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router,

    private activatedRoute: ActivatedRoute
  ) {}

  ngOnInit() {
    this.Cancel();

    this.LoadVehicles();

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.Ino = this.EditID;
        this.http
          .getData('qryvouchers?filter=VoucherID=' + this.EditID)
          .then((r: any) => {
            this.Voucher = r[0];
            this.Voucher.Date = GetDateJSON(new Date(r[0].Date));
            this.GetVehicle(this.Voucher.TransportID);
          });
      } else {
        this.EditID = '';
      }
      console.log(this.EditID);
    });
  }
  async FindINo() {
    let voucher: any = await this.http.getData('transportdetails/' + this.Ino);
    if (voucher.Expense > 0)
      this.router.navigate(['/transport/expense/', this.Ino]);
    else this.router.navigate(['/transport/expense/', this.Ino]);
  }
  LoadVehicles() {
    console.log('in vehicles');

    this.http.getData('transports').then((r: any) => {
      this.Vehicles = r;
    });
  }


  SaveData() {
    let voucherid = '';

    this.Voucher.Date = JSON2Date(this.Voucher.Date);
    if (this.EditID != '') {
      voucherid = '/' + this.EditID;
    }

    console.log(this.Voucher);
    this.http
      .postTask('transportvoucher' + voucherid, this.Voucher)
      .then((r) => {
        this.alert.Sucess('Expense Saved', 'Save', 1);
        if (this.EditID != '') {
          this.router.navigateByUrl('/transport/expense/');
        } else {
          this.Cancel()

        }
      })
      .catch((err) => {
        this.Voucher.Date = GetDateJSON();
        console.log(err);

        this.alert.Error(err.error.message, 'Error', 1);
      });
  }
  GetVehicle(VehicleID) {
    console.log(VehicleID);

    if (VehicleID && VehicleID !== '') {
      this.http
        .getData('transports?filter=TransportID=' + VehicleID)
        .then((r: any) => {
          this.curVehicle = r[0];
        });
    }
  }
  Round(amnt) {
    return Math.round(amnt);
  }

  Cancel() {
    this.Voucher = {
      Date:  GetDateJSON(new Date(getCurDate())),
      TransportID: 0,
      Details: null,
      Income: 0,
      Expense: 0,
    };

    this.router.navigateByUrl('/transport/expense');
  }
}
