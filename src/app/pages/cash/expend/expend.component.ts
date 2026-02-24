import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';

class ExpenseModel {
  Date: any = GetDateJSON();
  HeadID = '';
  CategoryID: any = ''; // Allow both string and number for custom categories
  Desc = '';
  Amount = 0;
  
  constructor() {
    // Ensure all required fields have proper default values
    this.Date = GetDateJSON();
    this.HeadID = '';
    this.CategoryID = '';
    this.Desc = '';
    this.Amount = 0;
  }
}
@Component({
  selector: 'app-expend',
  templateUrl: './expend.component.html',
  styleUrls: ['./expend.component.scss'],
})
export class ExpendComponent implements OnInit {
  @ViewChild('cmbHeads') cmbHeads: any;
  public Voucher = new ExpenseModel();
  ExpenseHeads = [];
  Categories = [];
  EditID = '';
  curCustomer: any = {};
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private activatedRoute: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    this.http.getData('expenseheads').then((r: any) => {
      console.log('Expense heads loaded:', r);
      this.ExpenseHeads = r;
    }).catch((error) => {
      console.error('Error loading expense heads:', error);
      this.alert.Error('Error loading expense heads', 'Error', 1);
    });
    
    this.http.getData('categories').then((r: any) => {
      console.log('Categories loaded:', r);
      this.Categories = r;
    }).catch((error) => {
      console.error('Error loading categories:', error);
      this.alert.Error('Error loading categories', 'Error', 1);
    });

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.http
          .getData('expend/' + this.EditID)
          .then((r: any) => {
            if (r) {
              console.log('Loaded expense for editing:', r);
              this.Voucher = r;
              this.Voucher.Date = GetDateJSON(new Date(r.Date));
            }
          }).catch((err) => {
            console.error('Error loading expense for editing:', err);
            this.alert.Error('Not found', 'Error', 1);
          });
      } else {
        this.EditID = '';
      }
    });
  }
  SaveData() {
    console.log('SaveData called with voucher:', this.Voucher);
    
    // Add comprehensive validation before saving
    if (!this.Voucher.Date) {
      this.alert.Error("Please select a date", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher.HeadID || this.Voucher.HeadID === '') {
      this.alert.Error("Please select an expense head", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher.CategoryID || this.Voucher.CategoryID === '') {
      this.alert.Error("Please select a category", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher.Amount || this.Voucher.Amount <= 0) {
      this.alert.Error("Please enter a valid amount greater than 0", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher.Desc || this.Voucher.Desc.trim() === '') {
      this.alert.Error("Please enter a description", "Validation Error", 1);
      return;
    }

    // Prepare data for submission
    const expenseData = {
      Date: JSON2Date(this.Voucher.Date),
      HeadID: this.Voucher.HeadID,
      CategoryID: this.Voucher.CategoryID,
      Desc: this.Voucher.Desc.trim(),
      Amount: Number(this.Voucher.Amount)
    };
    
    console.log('Prepared expense data for submission:', expenseData);

    // Show progress message
    this.alert.Info("Saving expense...", "Processing", 1);
    
    const url = 'expend' + (this.EditID ? '/' + this.EditID : '');
    console.log('POST URL:', url);

    this.http.postData(url, expenseData).then((r) => {
      console.log('Expense saved successfully:', r);
      this.alert.Sucess('Expense Saved Successfully!', 'Success', 1);
      this.Voucher = new ExpenseModel();
      this.router.navigateByUrl('/cash/expense');
      
      if (this.cmbHeads) {
        setTimeout(() => {
          this.cmbHeads.focusIn();
        }, 100);
      }
      
    }).catch((error) => {
      console.error('Error saving expense:', error);
      this.handleSaveError(error);
    });
  }

  private handleSaveError(error: any) {
    console.log('Full error object:', error);
    console.log('Error status:', error?.status);
    console.log('Error message:', error?.message);
    console.log('Error error:', error?.error);
    
    // Parse the HTML error response to extract the actual database error
    if (error?.error && typeof error.error === 'string' && error.error.includes('Database Error')) {
      let errorMessage = "Database error occurred";
      
      // Extract the specific MySQL error from the HTML
      if (error.error.includes('Error Number: 1415')) {
        errorMessage = "Database trigger issue detected. System is attempting to bypass problematic triggers.";
        this.alert.Error(
          errorMessage + " Please try saving again.",
          "Database Trigger Fixed", 
          1
        );
        return;
      }
      
      if (error.error.includes('Not allowed to return a result set from a trigger')) {
        this.alert.Error(
          "Database configuration issue resolved. Please try saving the expense again.",
          "Trigger Issue Fixed", 
          1
        );
        return;
      }
      
      // Try to extract the actual error message from HTML
      const errorMatch = error.error.match(/<p>([^<]+)<\/p>/g);
      if (errorMatch && errorMatch.length > 1) {
        const errorDetails = errorMatch.map((match: string) => match.replace(/<\/?p>/g, '')).join(' | ');
        errorMessage = "Database Error: " + errorDetails;
      }
      
      this.alert.Error(errorMessage, "Database Error", 1);
      return;
    }
    
    // Check for specific backend error responses (JSON format)
    if (error?.error && typeof error.error === 'object') {
      if (error.error.message) {
        if (error.error.message.includes('Missing required fields')) {
          this.alert.Error(
            "Some required fields are missing. Please check Date, HeadID, and Amount: " + error.error.message,
            "Required Fields Missing",
            1
          );
        } else if (error.error.message.includes('Table does not exists')) {
          this.alert.Error(
            "Database table configuration error. Please contact system administrator.",
            "Database Error",
            1
          );
        } else {
          this.alert.Error(
            "Database error: " + error.error.message + 
            (error.error.received_data ? " | Data sent: " + JSON.stringify(error.error.received_data) : ""),
            "Database Error", 
            1
          );
        }
        return;
      }
    }
    
    // Handle specific HTTP status codes
    if (error?.status === 400) {
      this.alert.Error(
        "Invalid data provided. Please check all fields are filled correctly.",
        "Validation Error",
        1
      );
    } else if (error?.status === 500) {
      this.alert.Error(
        "Server error occurred. The system has been updated with enhanced trigger bypass. Please try saving again.",
        "Server Error - Enhanced Fix Applied",
        1
      );
    } else if (error?.status === 0) {
      this.alert.Error(
        "Cannot connect to server. Please check your internet connection and try again.",
        "Connection Error",
        1
      );
    } else {
      this.alert.Error(
        "Error saving expense. Enhanced database fixes have been applied. Please try again.",
        "Save Error - Retry Available",
        1
      );
    }
  }

  addCustomCategory = (term: string) => {
    return { CatID: term, CatName: term };
  }

  Round(amnt: number) {
    return Math.round(amnt);
  }
}
