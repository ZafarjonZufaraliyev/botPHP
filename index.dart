// import 'dart:io';

// void main() {

//   stdout.write("Parallelogrammning asosini kiriting (a): ");
//   double a = double.parse(stdin.readLineSync()!);

//   stdout.write("Parallelogrammning balandligini kiriting (h): ");
//   double h = double.parse(stdin.readLineSync()!);

//   /
//   double S = a * h;

//   print("Parallelogrammning yuzi: $S");
// }


// import 'dart:io';

// void main() {
//   stdout.write("Oy nomini kiriting (masalan: Yanvar): ");
//   String oy = stdin.readLineSync()!.toLowerCase(); 

//   int kunlar;

//   switch (oy) {
//     case 'yanvar':
//     case 'mart':
//     case 'may':
//     case 'iyul':
//     case 'avgust':
//     case 'oktabr':
//     case 'dekabr':
//       kunlar = 31;
//       break;

//     case 'aprel':
//     case 'iyun':
//     case 'sentabr':
//     case 'noyabr':
//       kunlar = 30;
//       break;

//     case 'fevral':
      
//       kunlar = 28;
//       break;s

//     default:
//       print("Noto'g'ri oy nomi!");
//       return;
//   }

//   print("$oy oyida $kunlar kun bor.");
// }



// void main() {
  
//   List<int> numbers = [-5, 3, 0, 7, -2, 8, -1];


//   List<int> positiveNumbers = numbers.where((num) => num > 0).toList();

//   print("Asl list: $numbers");
//   print("Musbat sonlar listi: $positiveNumbers");
// }
